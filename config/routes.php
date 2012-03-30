<?php

/**
 * Part of the Formloader module for Fuel
 *
 * @package   Formloader
 * @version   1.1
 * @author    Tim Griesser
 * @license   MIT License
 * @copyright 2012 Tim Griesser
 * @link      http://formloader.tgriesser.com
 */
return array(

	// Routing for dynamic JS
	'formloader/js/(forms|fields|fieldsets|actions)/(:any)' => 'formloader/js/fetch/$2/$1',
	'formloader/js/(forms|fields|fieldsets|actions)'        => 'formloader/js/$1',

	'formloader/(forms|fields|fieldsets|actions)/((.*?)-(.*?)-(.*?))' => 'formloader/base/edit/$1/$2',
	'formloader/(forms|fields|fieldsets|actions)/(list|create)'       => 'formloader/base/$2/$1',
	'formloader/(forms|fields|fieldsets|actions)'					  => 'formloader/base/list/$1',
	'formloader/settings'                                             => 'formloader/base/settings',
	'formloader'                                                      => function()
	{
		\Response::redirect('formloader/forms');
	}
);