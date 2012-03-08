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

		$writable_paths = array(
			\Config::get('formloader.output_path').'output',
			\Config::get('formloader.output_path').'forms',
			\Config::get('formloader.output_path').'config'
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
			foreach (array('templates', 'output', 'forms', 'config') as $migrate_item)
			{
				$dir = \Config::get('formloader.bundle_source').DS.$migrate_item;
				$item_dirs = \File::read_dir($dir, 1);

				foreach ($item_dirs as $item_dir => $file)
				{
					if ( ! is_int($item_dir))
					{
						$fullpath = $dir.DS.$item_dir;
						$destination = \Config::get('formloader.output_path').$migrate_item.DS.$item_dir;
						\File::copy_dir($fullpath, $destination);
						\Cli::write("\t".'Copied '.$migrate_item.' from ' . $fullpath, 'green');
					}
					else
					{
						$fullpath = $dir.DS.$file;
						$destination = \Config::get('formloader.output_path').$migrate_item.DS.$file;
						\File::copy($fullpath, $destination);
						\Cli::write("\t".'Copied '.$migrate_item.' from ' . $fullpath, 'green');
					}
				}				
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