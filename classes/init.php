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
Route::set('api', 'armrest/<controller>(/<id>(/<relation>(/<relation_id>)))', array())
	->defaults(array(
		'directory'	=>	'api',
		'controller'=>	'armrest',
	));