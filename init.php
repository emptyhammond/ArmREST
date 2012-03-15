<?php defined('SYSPATH') or die('No direct script access.');
/**
 * ArmREST Init File 
 *
 * @author Matthew Hammond
 *
 * @filesource
 *
 * @category Module
 *
 * @subpackage Init File
 */
Route::set('armrest.rels', 'api/rels/<id>')
->defaults(array(
	'directory'  => 'armrest',
	'controller' =>	'rels',
	'action'     => 'index',
));
Route::set('armrest', 'api/<controller>(/<id>(/<relation>(/<relation_id>)))')
->defaults(array(
	'controller'=>	'armrest',
	'action'     => 'index',
));