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
 * Kohana_Controller_ArmREST_Rels class.
 * 
 * @extends Controller
 */
class Kohana_Controller_ArmREST_Rels extends Controller {
	
	/**
	 * action_rels function.
	 * 
	 * @access public
	 * @return void
	 */
	public function action_index()
	{
		$this->response->body(View::factory('armrest/rels/'.$this->request->param('id'))->render());
	}
}
