<?php

/**
 * Part of the Formloader package for Fuel
 *
 * @package   Formloader
 * @version   1.0
 * @author    Tim Griesser
 * @license   MIT License
 * @copyright 2012 Tim Griesser
 * @link      http://tgriesser.com
 **/
return array(
		
	/** Absolute path to Mustache, only needed in the preview **/
	'mustache_path' => PKGPATH . 'parser/vendor/mustache/Mustache.php',
	
	/** The directory within "views" which houses form templates **/
	'view_dir'  => 'bootstrap2',
	
	/**
	 * Whether we're redirecting the request on error (with form values persisted)
	 * @var bool
	 */
	'redirect_on_error' => true,
	
	/*
	Tables, for the MongoDB ! 
	'tables' => array(
		'groups'    => 'formloader_groups',
		'forms'     => 'formloader_forms',
		'fieldsets' => 'formloader_fieldsets',
		'fields'    => 'formloader_fields',
		'actions'   => 'formloader_actions'
	),
	*/
	
	/* The builder module, a simple GUI for form creation/editing */
	'builder' => array(

		'asset_source' => __DIR__ . '/../modules/formloader/assets/',
		
		'asset_destination' => 'assets/formloader/',
		
		'asset_subpaths' => array(
			'assets/formloader/tag-it/'
		),

		// If this module is enabled at all, even in development
		'use_module' => true,

		// Change this only if you understand how to protect the module from being completely public
		'dev_only'   => true,
				
		// Determines whether to load the module based on the above.
		'enabled' => function() {
			if (Config::get('formloader.builder.use_module') and (Fuel::$env === 'development') or ! Config::get('formloader.builder.dev_only'))
			{
				try
				{
					Package::loaded('loopforge') or Package::load('loopforge');
				}
				catch (\PackageNotFoundException $e)
				{
					echo "The Loopforge module is a dependency of this package/module, please grab it from: ";
					exit;
				}
				
				// Loads the items that are enabled for editing...
				Config::load('formbuilder', true);
				
				// Adds the module path and module
				$module_paths = Config::get('module_paths');
				array_push($module_paths, __DIR__ . '/../modules/');
				Config::set('module_paths', $module_paths);
				Fuel::add_module('formloader');
				
				// Adds the scaffolding and whitelists the scaffolding
				Autoloader::add_class('Formloader\\Formloader_Scaffold', PKGPATH.'formloader/modules/formloader/classes/scaffold.php');
				$whitelist = Config::get('security.whitelisted_classes');
				$whitelist[] = 'Formloader\\Formloader_Scaffold';
				Config::set('security.whitelisted_classes', $whitelist);
			}
		}
	)
);