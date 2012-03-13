<?php

namespace Fuel\Tasks;

/**
 * Part of the Formloader package for Fuel
 *
 * @package   Formloader
 * @version   1.1
 * @author    Tim Griesser <tim@tgriesser.com>
 * @license   MIT License
 * @copyright 2012 Tim Griesser
 * @link      http://formloader.tgriesser.com
 */
class Formloader
{
	/**
	 * Installation script... does the following
	 * 1. Creates/makes writable the output, forms, and config directories in the APPPATH.'modules/formloader'
	 * 2. Makes the templates directory, and copies over the templates from the PKGPATH.'formloader/templates'
	 * 3. Moves the assets to a publicly accessible location
	 */
	public static function run($public_path = 'public')
	{
		// Load the formloader config
		\Config::load('formloader');
		
		// Add the asset destination
		$asset_destination = DOCROOT.$public_path.DS.\Config::get('formloader.builder.asset_destination');

		$source = \Config::get('formloader.bundle_source') . '/';
		$destination = \Config::get('formloader.output_path');

		$writable_paths = array(
			$destination.'output',
			$destination.'forms',
			$destination.'config'
		);
		
		/**
		 * chmod 0777 each of the writable paths above
		 */
		foreach ($writable_paths as $path)
		{
			if ( ! is_dir($path))
			{
				mkdir($path, 0777, true);
			}
			
			if (@chmod($path, 0777))
			{
				\Cli::write("\t".'Made writable: '.$path, 'green');
			}

			else
			{
				\Cli::write("\t".'Error: Failed to make writable: '.$path, 'red');
			}
		}
		
		/**
		 * Make the templates path
		 */
		if ( ! is_dir(\Config::get('formloader.output_path').'templates'))
		{
			mkdir(\Config::get('formloader.output_path').'templates', 0755);
		}
		
		/**
		 * Move the templates...
		 */
		try
		{
			/**
			 * Make sure that every file is writable as we go through the
			 * directories...
			 */
			$stack = array();
			
			/**
			 * Recursively moves individual items on the directory tree, chmodding each to 0777
			 * 
			 * @param array   
			 * @param string  type
			 * @param function
			 */
			$move_dirs = function($dir_tree, $self) use (&$stack, $source, $destination)
			{
				foreach ($dir_tree as $k => $v)
				{
					// This is a file...
					if (is_int($k))
					{
						// We need a matching stack minus the first element...
						$filepath = implode($stack) . $v;

						$src  = $source . $filepath;
						$dst  = $destination . $filepath;
						
						try
						{
							\File::copy($src, $dst);
							@chmod($dst, 0777);
						}
						catch (\Exception $e)
						{
							\Cli::write("\t" . 'Error: ' . $e->getMessage());
						}
					}
					// This is a directory
					else
					{
						// Go up a level...
						array_push($stack, $k);
						
						// This is the destination path
						$dest_path = $destination . implode($stack);
						
						// Create the directories with the appropriate permissions as we go through
						if ( ! is_dir($dest_path))
						{
							mkdir($dest_path, 0777);
						}
						
						$self($v, $self);
						array_pop($stack);
					}
				}
			};
			
			// Go through each individual portable directory...
			foreach (array('templates', 'output', 'forms', 'config') as $migrate_item)
			{
				// This will be the base of the stack
				$migrate_item .= '/';
				
				\Cli::write("\t" . 'Moving: '. $migrate_item, 'green');

				// Create a fresh stack for the $move_dirs use()...
				$stack = array(
					$migrate_item
				);
				
				$move_dirs(\File::read_dir($source.$migrate_item), $move_dirs);
			}
		}
		catch (\FileAccessException $e)
		{
			\Cli::write("\t".'Error moving '.$migrate_item.': '.$e->getMessage(), 'red');

			if (isset($fullpath))
			{
				\Cli::write("\t".'Path: '.$fullpath, 'red');
			}
			if (isset($destination))
			{
				\Cli::write("\t".'Destination: '.$destination, 'red');
			}
			self::exit_script();
		}

		\Cli::write("\t".'Copied assets from '.\Config::get('formloader.builder.asset_source').' to '.$asset_destination, 'green');
		
		if (is_dir($asset_destination . 'formloader'))
		{
			\Cli::write("\t".'Directory: '.$asset_destination.' already exists, please delete it and run "php oil r formloader" again to update', 'yellow');
			self::exit_script();
		}
		else
		{
			$exception = '';
						
			// Move our assets over from the Formloader/assets folder to the
			// asset destination so they are globally available
			if (mkdir($asset_destination, 0755, true))
			{
				try
				{
					\File::copy_dir(\Config::get('formloader.builder.asset_source'), $asset_destination);
				}
				catch (\FileAccessException $e)
				{
					$exception = $e->getMessage();
					\Cli::write("\t".'Error moving assets: '.$exception, 'red');
					self::exit_script();
				}

				\Cli::write("\t".'Copied assets from '.\Config::get('formloader.builder.asset_source').' to '.$asset_destination, 'green');
			}
			else
			{
				\Cli::write("\t".'Error: Failed to make writable: '.$asset_destination, 'red');
				self::exit_script();
			}
		}
	}
	
	/**
	 * Break on error
	 */
	public static function exit_script()
	{
		\Cli::write("\t".'Script failed, please fix the above errors or initialize manually.', 'red');
		exit;
	}
}