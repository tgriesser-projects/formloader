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
	public static function run($public_path = 'public')
	{
		// Load the formloader config
		\Config::load('formloader');
		
		// Add the asset destination
		$asset_destination = DOCROOT.$public_path.DS.\Config::get('formloader.builder.asset_destination');

		$writable_paths = array(
			\Config::get('formloader.output_path').DS.'output',
			\Config::get('formloader.output_path').DS.'forms',
			\Config::get('formloader.output_path').DS.'config'
		);
		
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
				\Cli::write("\t".'Failed to make writable: '.$path, 'red');
			}
		}
		
		if ( ! is_dir(\Config::get('formloader.output_path').DS.'templates'))
		{
			mkdir(\Config::get('formloader.output_path').DS.'templates', 0755);
		}
		try
		{
			\File::
			
			\File::copy_dir(\Config::get('formloader.builder.asset_source'), $asset_destination);
		}
		catch (\FileAccessException $e)
		{
			$exception = $e->getMessage();
		}

		if ( ! empty($exception))
		{
			\Cli::write("\t".'Error moving templates: '.$exception, 'red');
		}
		else
		{
			\Cli::write("\t".'Copied assets from '.\Config::get('formloader.builder.asset_source').' to '.$asset_destination, 'green');
		}
		
		
		if (is_dir($asset_destination . 'formloader'))
		{
			\Cli::write("\t".'Directory: '.$asset_destination.' already exists, please delete it and run "php oil r formloader" again to update', 'yellow');
			exit;
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
				}

				if ( ! empty($exception))
				{
					\Cli::write("\t".'Error moving assets: '.$exception, 'red');
				}
				else
				{
					\Cli::write("\t".'Copied assets from '.\Config::get('formloader.builder.asset_source').' to '.$asset_destination, 'green');
				}
			}
			else
			{
				\Cli::write("\t".'Error: Failed to make writable: '.$asset_destination, 'red');				
			}
		}
	}
}