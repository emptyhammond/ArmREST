<?php defined('SYSPATH') or die('No direct script access.');
/**
 * ArmREST Init file
 * 
 * @package    Kohana/ArmREST
 * @category   Init
 * @author     Matthew Hammond
 * @copyright  (c) 2012 Matthew Hammond
 * @license    http://kohanaframework.org/license
 */
Route::set('ArmREST', '<controller>(/<id>)')
->defaults(array(
	'controller' => 'ArmREST',
	'action'     => 'index',
));