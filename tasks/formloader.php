<?php

namespace Fuel\Tasks;

/**
 * Part of the Formloader package for Fuel
 *
 * @package   Formloader
 * @version   1.0
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
			\Config::get('formloader.output_path').'/output',
			\Config::get('formloader.output_path').'/forms',
			\Config::get('formloader.output_path').'/config'
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
		
		// -----------
		
		/**
		 * Make the templates path
		 */
		if ( ! is_dir(\Config::get('formloader.output_path').'/templates'))
		{
			mkdir(\Config::get('formloader.output_path').'/templates', 0755);
		}
		
		/**
		 * Move the templates...
		 */
		try
		{
			$template_dirs = \File::read_dir(\Config::get('formloader.template_source'), 1);
			
			foreach ($template_dirs as $tdir => $empty)
			{
				$template = \Config::get('formloader.template_source') . $tdir;
				$destination = \Config::get('formloader.output_path') . '/templates' . $tdir;
				\File::copy_dir($template, $destination);
				\Cli::write("\t".'Copied templates from ' . $template, 'green');
			}
		}
		catch (\FileAccessException $e)
		{
			\Cli::write("\t".'Error moving templates: '.$e->getMessage(), 'red');
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
		\Cli::write("\t".'Script failed, please fix the above errors', 'red');
		exit;
	}
	
}