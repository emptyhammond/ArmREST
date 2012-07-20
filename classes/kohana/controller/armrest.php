<?php defined('SYSPATH') or die('No direct script access.');
/**
 * ArmREST Template Controller
 * 
 * The ArmREST Template Controller.
 *
 * @package    Kohana/ArmREST
 * @category   Controller
 * @author     Matthew Hammond
 * @copyright  (c) 2012 Matthew Hammond
 * @license    http://kohanaframework.org/license
 */

/**
 * Controller_Kohana_Armrest class.
 * 
 * @extends Controller_REST
 */
class Kohana_Controller_ArmREST extends Controller_REST {

	/**
	 * _accept
	 * 
	 * @var mixed
	 * @access protected
	 */
	protected $_accept;
	
	/**
	 * _accept_lang
	 * 
	 * @var mixed
	 * @access protected
	 */
	protected $_accept_lang;
	
	/**
	 * _accept_charset
	 * 
	 * @var mixed
	 * @access protected
	 */
	protected $_accept_charset;
	
	/**
	 * _accept_strict
	 * 
	 * (default value: TRUE)
	 * 
	 * @var boolean
	 * @access protected
	 */
	protected $_accept_strict;
	
	/**
	 * _config
	 * 
	 * @var mixed
	 * @access protected
	 */
	protected $_config;
	
	/**
	 * output
	 * 
	 * @var mixed
	 * @access protected
	 */
	protected $output;
	
	/**
	 * _table
	 * 
	 * @var string
	 * @access protected
	 */
	protected $_table;
	
	/**
	 * _model
	 * 
	 * @var string
	 * @access protected
	 */
	protected $_model;
	
	/**
	 * _collection
	 * 
	 * @var boolean
	 * @access protected
	 */
	protected $_collection = true;
	
	/**
	 * __construct function.
	 * 
	 * @access public
	 * @param Request $request
	 * @param Response $response
	 * @param array $accept (default: NULL)
	 * @param array $accept_charset (default: NULL)
	 * @param array $accept_language (default: NULL)
	 * @param mixed $accept_strict (default: FALSE)
	 * @return void
	 */
	public function __construct(Request $request, Response $response, array $accept = NULL,array $accept_charset = NULL, array $accept_language = NULL, $accept_strict = FALSE)
	{	
		$this->_config = Kohana::$config->load('armrest');
		$this->_accept = $this->_config['types'];
		$this->_accept_langs = $this->_config['langs'];
		$this->_accept_charset = $this->_config['charset'];
		$this->_accept_strict = $this->_config['strict'];
		$this->_config = Kohana::$config->load('armrest');
		
		parent::__construct($request, $response, $accept, $accept_charset, $accept_language, $accept_strict);
	}
	
	/**
	 * before function.
	 * 
	 * @access public
	 * @return void
	 */
	public function before()
	{
		parent::before();
		
		$this->_table = $this->request->controller();
		
		$this->_model = Inflector::singular($this->_table);
	}
	
	/**
	 * action_index function.
	 * 
	 * Gets a set of pages based on a uuid in the specified format
	 * 
	 * Possible query string parameters:
	 * - fields comma delimited list of fields e.g. fields=id,name,slug
	 * - order key and ASC|DESC e.g. order[name]=DESC
	 * - limit e.g. limit=1,2
	 * - key=value pairs that match field names will be parsed into where clauses e.g. name=matt === WHERE name = 'matt'
	 *
	 * @access public
	 * @return void
	 * @todo Add fields to singular
	 * @todo Add search
	 */
	public function action_index()
	{
		if ( ($id = $this->request->param('id')) && ($relation_id = $this->request->param('relation_id')) )
		{
			/*
			A specific relation has been requested.
			Get the relation based on the relation_id.
			Checking if it exists and if it has a relationship with the base resource.
			*/
			
			//check that base resource exists
			$object = ORM::factory($this->_model, $id);
			
			//check base resource is loaded
			if ( ! $object->loaded() )
			{
				throw new Http_Exception_400('Bad Request');
			}
			
			//reset  _table and _model to required relation resource
			$this->_table = $this->request->param('relation');
			
			$this->_model = Inflector::singular($this->_table);
			
			//try and get relation based on request
			$relation = ORM::factory($this->_model, $relation_id);
			
			//if relation resource not loaded throw 400
			if ( ! $relation->loaded() )
			{
				throw new Http_Exception_400('Bad Request');
			}
			
			//if base resource doesn't have a relationship, throw a 400
			if ( ! $object->has($this->_table, $relation))
			{
				throw new Http_Exception_400('Bad Request');
			}
			
			
			$link = array('link' => array('rel' => Route::url('armrest.rels', array('id' => 'self'), true), 'href' => Route::url('armrest', array('controller' => $this->_table, 'id' => $relation->id), true)));
			
			unset($relation->id); //we don't need it - we know which resource we requested
			
			$this->_collection = false;
			
			$this->response->status(200); //response with a 200

			$this->response->headers('Last-Modified', Armrest::last_modified($resource));
					
			Log::instance()->add(Log::INFO,'MESSAGE HERE');
			
			$this->output = array(array_merge($relation->as_array(), $link)); //output = array of requested relation resource
		}
		elseif ( ($relation = $this->request->param('relation')) && ! $this->request->param('relation_id'))
		{
			/*
			A collection of relations has been requested.
			Check that relationship exists.
			*/
			
			$resource = ORM::factory($this->_model, $this->request->param('id'));
			
			$this->_table = $relation;
			
			$this->_model = Inflector::singular($this->_table);
			
			$columns = ORM::factory($this->_model)->list_columns();
			
			$query = $resource->{$this->request->param('relation')};
			
			foreach(array_intersect_key($_GET, $columns) as $key => $value)
			{
				$query->and_where($key, 'LIKE', $value);
			}
			
			if(isset($_GET['order_by']))
			{	
				foreach($_GET['order_by'] as $key => $value)
				{
					$query->order_by($key, $value);
				}
			}
			
			if(isset($_GET['limit']))
			{
				$query->limit($_GET['limit']);
			}
			
			$objects = array();
			
			if ($query->loaded())
			{
				array_push($objects, UTF8::clean($query->as_array()));
			}
			else
			{
				foreach($query->find_all() as $relation)
				{	
					$link = array('link' => array('rel' => Route::url('armrest.rels', array('id' => 'self'), true), 'href' => Route::url('armrest', array('controller' => $this->_table, 'id' => $relation->id), true)));
					
					array_push($objects, UTF8::clean(array_merge_recursive($relation->as_array(), $link)));
				}
				unset($link, $relation);
			}

			$this->response->status(200);
			
			$this->response->headers('Last-Modified', Armrest::last_modified($resource));
	
			Log::instance()->add(Log::INFO,'MESSAGE HERE');
			
			//echo View::factory('profiler/stats');
			$this->output = $objects;
		}
		elseif (($id = $this->request->param('id')) && !$this->request->param('relation_id'))
		{
			/*
			A specific resource has been requested.
			Return resource if it exists.
			*/
			$object = ORM::factory($this->_model, $id);
			
			if( ! $object->loaded() )
			{
				throw new Http_Exception_400('Bad Request');
			}
			
			$link = array('link' => array('rel' => Route::url('armrest.rels', array('id' => 'self'), true), 'href' => Route::url('armrest', array('controller' => $this->_table, 'id' => $object->id), true)));
			
			$this->_collection = false;
						
			$this->response->status(200);
			
			$this->response->headers('Last-Modified', Armrest::last_modified($object));
			
			Log::instance()->add(Log::INFO,'MESSAGE HERE');
			
			unset($object->id); //we don't need it - we know which page we requested
						
			$this->output = array(array_merge($object->as_array(), $link));
		}
		else
		{
			/*
			A collection of resources has been requested.
			*/
			$objects = array();
			
			$object = ORM::factory($this->_model);
			
			$query = DB::select();
			
			if(isset($_GET['fields']))
			{
				$query->select('id');
				
				foreach(explode(',', $_GET['fields']) as $field)
				{
					$query->select($field);	
				}
			}
			else
			{
				$query->select('*');
			}
			
			$query->from($this->_table);
			
			foreach(array_intersect_key($_GET, $object->list_columns()) as $key => $value)
			{
				$query->and_where($key, 'LIKE', $value);
			}
			
			if(isset($_GET['order_by']))
			{
				foreach($_GET['order_by'] as $key => $value)
				{
					$query->order_by($key, $value);
				}
			}
			
			if(isset($_GET['limit']))
			{
				$query->limit($_GET['limit']);
			}
			
			foreach($query->execute()->as_array() as $object)
			{
				$link = array('link' => array('rel' => Route::url('armrest.rels', array('id' => 'self'), true), 'href' => Route::url('armrest', array('controller' => $this->_table, 'id' => $object['id']), true)));
				
				array_push($objects, UTF8::clean(array_merge($object, $link)));
			}
			
			var_dump($query);
			
			$link = array('link' => array('rel' => Route::url('armrest.rels', array('id' => 'self'), true), 'href' => Route::url('armrest', array('controller' => $this->_table), true)));
			
			$this->response->status(200);
			
			$this->response->headers('Last-Modified', Armrest::last_modified($object));
			
			Log::instance()->add(Log::INFO,'MESSAGE HERE');
			
			$this->output = array_merge($objects, $link);
		}
		
		if ($this->_config['ETags'])
		{
			$this->response->headers('ETag',$this->response->generate_etag());
		}
	}
	
	/**
	 * action_create function.
	 * 
	 * Creates a new page
	 * 
	 * @access public
	 * @return void
	 */
	public function action_create()
	{
		$object = ORM::factory($this->_model);
		
		$object->values($_POST);
		
		if($object->validation()->check())
		{
			$object->save();
			
			$this->response->status(201);
			
			Log::instance()->add(Log::INFO,'MESSAGE HERE');
			
			$this->output = UTF8::clean(array_merge($object->as_array(), $link = array('link' => array('rel' => Route::url('armrest.rels', array('id' => 'self'), true), 'href' => Route::url('armrest', array('controller' => $this->_table, 'id' => $object->id), true)))));
		}
		else
		{
			$this->response->status(400);
			
			Log::instance()->add(Log::INFO,'MESSAGE HERE');
			
			$this->output = array($object->validation()->errors());
		}
	}
	
	/**
	 * action_update function.
	 * 
	 * Update an existing page
	 * 
	 * @access public
	 * @return void
	 */
	public function action_update()
	{
		$object = ORM::factory($this->_model, $this->request->param('id'));
		
		if( ! $object->loaded() )
		{
			throw new Http_Exception_400('Bad Request');
		}
				
		$putdata = '';
		
		$httpContent = fopen('php://input', 'r');
		
        while ($data = fread($httpContent, 1024)) {
			$putdata .= $data;
        }
		
		$array = $putdata ? json_decode($putdata,true) : array(); //TODO: Handle XML and other format requests
		
		$object->values($array);
		
		if($object->validation()->check())
		{
			$object->update();
			
			unset($object->id); //we don't need it - we know which page we requested
			
			$this->output = $object->as_array();
		}
		else
		{
			$this->response->status(409);
			
			Log::instance()->add(Log::INFO,'MESSAGE HERE');
			
			$this->output = array('error' => $object->validation()->errors());
		}
	}
	
	/**
	 * action_delete function.
	 * 
	 * Delete an existing page
	 * 
	 * @access public
	 * @return void
	 */
	public function action_delete()
	{
		$object = ORM::factory($this->_model, $this->request->param('id'));
		
		if( ! $object->loaded() )
		{
			throw new Http_Exception_400('Bad Request');
		}
		
		/** 
		 * If user isn't allowed to delete
		 * return $this->response->status(405);
		 * and don't do the delete
		 * else...
		**/		
		$object->delete();
		$this->response->status(204);
		Log::instance()->add(Log::INFO,'MESSAGE HERE');

		$this->output = false;
	}
	
	/**
	 * after function.
	 * 
	 * @access public
	 * @return void
	 */
	public function after()
	{
		$types = array_keys(Request::accept_type());
		
		if(in_array($mime = 'application/xml', $types) or in_array($mime = 'text/xml', $types))
		{	
			$this->response->headers('Content-Type',$mime);
			
			$this->response->body(ArmREST::xml($this->output, $this->_table, $this->_model, $this->_collection));
		}		
		elseif(in_array('text/html', $types))
		{	
			$this->response->headers('Content-Type', 'text/html' );			
		
			$this->response->body(ArmREST::html($this->output));
		}
		else // text/plain
		{
			$mime = 'text/javascript';
			$this->response->headers('Content-Type', (isset($_REQUEST['callback']) ? 'text/javascript' : $mime) );			
		
			$this->response->body(ArmREST::json($this->output));
		}
		
		parent::after();
	}
}