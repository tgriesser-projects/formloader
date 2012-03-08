<?php

/**
 * Part of the Formloader package for Fuel
 *
 * @package   Formloader
 * @version   1.0
 * @author    Tim Griesser
 * @license   MIT License
 * @copyright 2012 Tim Griesser
 * @link      http://formloader.tgriesser.com
 **/
return array(
	
	/**
	 * Where everything output by the Formbuilder is dumped...
	 * so we are able to version forms, etc. while keeping formloader
	 * an independent package/module
	 * @var string
	 */
	'output_path' => APPPATH . 'modules/formloader',

	/**
	 * Location of the template scaffolding to be moved for form creation
	 * @var
	 */
	'template_source' => __DIR__ . '../templates/',
	
	/** 
	 * Absolute path to Mustache, only needed in the preview
	 * @var string
	 */
	'mustache_path' => PKGPATH . 'parser/vendor/mustache/Mustache.php',
	
	/**
	 * The default directory within "templates" which houses form template skeletons
	 * @var string
	 */
	'template_dir'  => 'bootstrap2',
	
	/**
	 * Whether we're redirecting the request on error (with form values persisted)
	 * @var bool
	 */
	'redirect_on_error' => true,
	
	/**
	 * The Formloader's module, a simple GUI for form creation/editing
	 * @var array
	 */
	'builder' => array(

		/**
		 * If this module is enabled at all, even in development
		 * @var bool
		 */
		'use_module' => true,

		/**
		 * Change this only if you understand how to protect the module from being completely public
		 * @var bool
		 */
		'dev_only'   => true,
		
		/**
		 * Location of the files to be made public
		 * @var string
		 */
		'asset_source' => __DIR__ . '/../modules/formloader/assets/',
		
		/**
		 * The destination of the asset files for the oil refine
		 * @var string
		 */
		'asset_destination' => 'assets/formloader/',
		
		/**
		 * These paths are added to the asset loader
		 * @var array
		 */
		'asset_subpaths' => array(
			'assets/formloader/tag-it/'
		),
				
		/**
		 * Determines whether to load the module based on the above settings & $env
		 */
		'enabled' => function()
		{
			if (Config::get('formloader.builder.use_module') and (Fuel::$env === 'development' or ! Config::get('formloader.builder.dev_only')))
			{
				try
				{
					try
					{
						Package::loaded('loopforge') or Package::load('loopforge');
					}
					catch (\PackageNotFoundException $e)
					{
						throw new FormloaderException("The Loopforge module is a dependency of this package/module. \n
						Please grab it from: git@github.com:tgriesser/loopforge.git and put it in the packages directory");
					}				
					try
					{
						Package::loaded('parser') or Package::load('parser');
					}
					catch (\PackageNotFoundException $e)
					{
						throw new FormloaderException("This module requires the Mustache/Parser package, please add it to the packages directory!");
					}
				}
				catch (\FormloaderException $e)
				{
					echo \View::forge('error', array(
						'message'      => $e->getMessage()
					));
					die;
				}
				
				/**
				 * Loads the items that are enabled for editing...
				 */
				$builder_config = \Config::get('formloader.output_path').'/config/formbuilder.php';
				Config::load($builder_config, 'formbuilder');

				/**				
				 * Adds the module path and module
				 */
				$module_paths = Config::get('module_paths');
				array_push($module_paths, __DIR__ . '/../modules/');
				Config::set('module_paths', $module_paths);
				Fuel::add_module('formloader');

				/**								
				 * Adds the scaffolding and whitelists the scaffolding
				 */
				Autoloader::add_class('Formloader\\Formloader_Scaffold', PKGPATH.'formloader/modules/formloader/classes/scaffold.php');
				$whitelist = Config::get('security.whitelisted_classes');
				$whitelist[] = 'Formloader\\Formloader_Scaffold';
				Config::set('security.whitelisted_classes', $whitelist);
			}
		}
	)
);