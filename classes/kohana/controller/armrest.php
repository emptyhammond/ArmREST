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
			
			$this->output = $object->as_array();
		}
		else
		{
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
		
		$array = $putdata ? json_decode($putdata,true) : array();
		
		$object->values($array);
		
		if($object->validation()->check())
		{
			$object->update();
			
			$this->output = $object->as_array();
		}
		else
		{
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
		
		$id = $object->id;
		
		$object->delete();
		
		$this->output = array('response' => ucfirst($this->_model)." id:$id deleted successfully");
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
		
		$contenttype = 'application/json';
		
		$output = 'No output';
		
		if ( in_array($mime = 'text/javascript', $types) or in_array($mime = 'application/javascript', $types) or in_array($mime = 'application/json', $types) )
		{
			$contenttype = (isset($_REQUEST['callback']) ? 'text/javascript' : $mime);
		
			$output = ArmREST::json($this->output);
		}			
		elseif (in_array($mime = 'application/xml', $types) or in_array($mime = 'text/xml', $types))
		{
			$contenttype = $mime;
			
			$output = ArmREST::xml($this->output, $this->_table, $this->_model, $this->_collection);
		}		
		elseif (in_array('text/html', $types))
		{
			$contenttype = 'text/html';
			$output = ArmREST::html($this->output);
		}
		else // text/plain
		{
			$contenttype = 'text/plain';
			$output = ArmREST::text($this->output);
		}
		
		/**
		 * Content Length needs to be set in order to prevrent null outputs on some clients
		 */
		$this->response->headers('Content-Length', strlen($output));
		$this->response->headers('Content-Type', (isset($_REQUEST['callback']) ? 'text/javascript' : $mime) );
		$this->response->body($output);
		
		parent::after();
		
		if (in_array(Arr::get($_SERVER, 'HTTP_X_HTTP_METHOD_OVERRIDE', $this->request->method()), array(
			HTTP_Request::GET)))
		{
			$this->response->headers('cache-control', 'max-age='.Kohana::$config->load('armrest.max-age').', private, must-revalidate');	
		}
	}
}