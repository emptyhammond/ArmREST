<?php defined('SYSPATH') or die('No direct access allowed.');

return array(
	'ETags' => true,
	'types' => array(
		'text/plain',
		'text/html',
		'text/javascript',
		'application/json',
		'application/javascript',		
		'application/xml',
		'text/xml',
	),
	'langs' => array(
		'en_US',
		'en_GB',
	),
	'charset' => array(
		'utf-8',
		'ISO-8859-1',
	),
	'strict' => true,
	'max-age' => 31536000, //for gets
);