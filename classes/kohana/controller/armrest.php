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
	protected $_accept = array(
		'text/plain',
		'text/html',
		'text/javascript',
		'application/json',
		'application/xml',
		'text/xml'
	);
	
	/**
	 * _accept_lang
	 * 
	 * @var mixed
	 * @access protected
	 */
	protected $_accept_lang = array(
		'en_US',
		'en_GB'
	);
	
	/**
	 * _accept_charset
	 * 
	 * @var mixed
	 * @access protected
	 */
	protected $_accept_charset = array(
		'utf-8',
		'ISO-8859-1'
	);
	
	/**
	 * _accept_strict
	 * 
	 * (default value: TRUE)
	 * 
	 * @var mixed
	 * @access protected
	 */
	protected $_accept_strict = TRUE;
	
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
	 * @var mixed
	 * @access protected
	 */
	protected $_table;
	
	/**
	 * _model
	 * 
	 * @var mixed
	 * @access protected
	 */
	protected $_model;
	
	
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
		if($this->request->param('id'))
		{
			$object = ORM::factory($this->_model, $this->request->param('id'));
					
			if( ! $object->loaded() )
			{
				throw new Http_Exception_404(ucfirst($this->_model)." doesn't exist");
			}
			
			unset($object->id); //we don't need it - we know which page we requested
			
			$this->response->status(200);
			
			$this->output = array($object->as_array());
		}
		else
		{
			$objects = array();
			
			$object = ORM::factory($this->_model);
			
			$query = DB::select();
			
			if(isset($_GET['fields']))
			{
				foreach(explode(',', $_GET['fields']) as $field)
				{
					$query->select($field);
				}
			}
			
			$query->from($this->_table);
			
			foreach(array_intersect_key($_GET, $object->list_columns()) as $key => $value)
			{
				$query->and_where($key, '=', $value);
			}
			
			if(isset($_GET['order']))
			{	
				foreach($_GET['order'] as $key => $value)
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
				array_push($objects, UTF8::clean($object));
			}
			
			$this->response->status(200);
			
			$this->output = $objects;
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
			
			$this->output = $object->as_array();
		}
		else
		{
			$this->response->status(400);
			
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
			throw new Http_Exception_404(ucfirst($this->_model)." doesn't exist");
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
			throw new Http_Exception_404(ucfirst($this->_model)." doesn't exist");
		}
		
		if(0===1) // If user isn't allowed to delete
		{
			$object->delete();
			
			$this->response->status(405);	
		}
		else
		{
			$this->response->status(204);
		}
		
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
		
		if( in_array($mime = 'text/javascript', $types) or in_array($mime = 'application/json', $types) )
		{
			$this->response->headers('Content-Type', (isset($_REQUEST['callback']) ? 'text/javascript' : $mime) );			
		
			$this->response->body(ArmREST::json($this->output));
		}			
		elseif(in_array($mime = 'application/xml', $types) or in_array($mime = 'text/xml', $types))
		{
			$this->response->headers('Content-Type',$mime);
			$this->response->body(ArmREST::xml($this->output, $this->_table, $this->_model));
		}		
		elseif(in_array('text/html', $types))
		{	
			$this->response->headers('Content-Type', 'text/html' );			
		
			$this->response->body(ArmREST::html($this->output));
		}
		else // text/plain
		{	
			$this->response->headers('Content-Type', 'text/html' );			
		
			$this->response->body(ArmREST::text($this->outputs));
		}
		
		parent::after();
	}
}