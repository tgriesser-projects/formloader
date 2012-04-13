<?php

namespace Formloader;

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
class Formloader_Migration
{
	/**
	 * Determines what action the self::write() takes
	 * @var bool
	 */
	protected static $is_cli = false;

	/**
	 * All items written to the output are stored here
	 * @var array
	 */
	protected static $writes = array();

	/**
	 * Loads the init
	 */
	public static function _init()
	{
		// Load the formloader config
		\Config::load('formloader');
	}

	/**
	 * Updates the assets from the 'formloader/update' panel
	 */
	public static function migrate_app()
	{
		static::$is_cli = false;
		static::migrate();
		return self::$writes;
	}

	/**
	 * Runs all the migration from the CLI
	 */
	public static function migrate_cli($public_path = 'public')
	{
		static::$is_cli = true;
		static::migrate($public_path);
	}

	/**
	 * Migrates the formloader
	 */
	public static function migrate($public_path = '')
	{
		// Add the asset destination
		$asset_destination = DOCROOT.(static::$is_cli ? $public_path . DS : '').\Config::get('formloader.builder.asset_destination');

		$source = \Config::get('formloader.bundle_source') . '/';
		$destination = \Config::get('formloader.output_path');

		$writable_paths = array(
			$destination.'output',
			$destination.'forms',
			$destination.'templates',
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
				self::write("\t".'Made writable: '.$path, 'green');
			}
			else
			{
				self::write("\t".'Error: Failed to make writable: '.$path, 'red');
			}
		}
	
		/**
		 * Move everything...
		 */
		try
		{
			/**
			 * Make sure that every file is writable as we go through the
			 * directories...
			 */
			$stack = array();
			
			/**
			 * Alias to the 
			 */
			$is_cli = static::$is_cli;

			/**
			 * Recursively moves individual items on the directory tree, chmodding each to 0777
			 * 
			 * @param array   
			 * @param string  type
			 * @param function
			 */
			$move_dirs = function($dir_tree, $self) use (&$stack, &$is_cli, $source, $destination)
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
							if ( ! $is_cli and file_exists($dst))
							{
								\File::delete($dst);
								Formloader_Migration::write("\t" . 'Deleted: ' . $dst, 'yellow');
							}

							\File::copy($src, $dst);
							@chmod($dst, 0777);
							Formloader_Migration::write("\t" . 'Moved ' . $v . ' successfully', 'green');
						}
						catch (\Exception $e)
						{
							Formloader_Migration::write("\t" . $v . ' Error: ' . $e->getMessage(), 'red');
						}
					}
					// This is a directory
					else
					{
						// Go up a level...
						array_push($stack, $k);
						
						// This is the destination path
						$dest_path = $destination . implode($stack);
						
						// Removes the path if it's already created and we're running the updater
						if (is_dir($dest_path))
						{
							\File::delete_dir($dest_path);
							Formloader_Migration::write("\t".'Deleted: ' . $dest_path, 'yellow');
						}

						// Create the directories with the appropriate permissions as we go through
						if ( ! is_dir($dest_path))
						{
							Formloader_Migration::write("\t".'Creating: ' . $dest_path, 'green');
							mkdir($dest_path, 0777);
						}
						
						$self($v, $self);
						array_pop($stack);
					}
				}
			};
			
			$migrate_stack = array('templates', 'output', 'forms', 'config');

			// Go through each individual portable directory...
			foreach ($migrate_stack as $migrate_item)
			{
				// This will be the base of the stack
				$migrate_item .= '/';
				
				self::write("\t" . 'Moving: '. $migrate_item, 'green');

				// Create a fresh stack for the $move_dirs use()...
				$stack = array(
					$migrate_item
				);
								
				$move_dirs(\File::read_dir($source.$migrate_item), $move_dirs);
			}
		}
		catch (\FileAccessException $e)
		{
			self::write("\t".'Error moving '.$migrate_item.': '.$e->getMessage(), 'red');

			if (isset($fullpath))
			{
				self::write("\t".'Path: '.$fullpath, 'red');
			}
			if (isset($destination))
			{
				self::write("\t".'Destination: '.$destination, 'red');
			}
			self::exit_script();
		}

		// Create the directories with the appropriate permissions as we go through
		if (is_dir($asset_destination))
		{
			\File::delete_dir($asset_destination);
			self::write("\t".'Deleted: ' . $asset_destination, 'yellow');
		}

		$exception = '';
					
		// Move our assets over from the Formloader/assets folder to the
		// asset destination so they are globally available
		if (mkdir($asset_destination, 0777, true))
		{
			try
			{
				\File::copy_dir(\Config::get('formloader.builder.asset_source'), $asset_destination);
			}
			catch (\FileAccessException $e)
			{
				$exception = $e->getMessage();
				self::write("\t".'Error moving assets: '.$exception, 'red');
				self::exit_script();
			}

			self::write("\t".'Copied assets from '.\Config::get('formloader.builder.asset_source').' to '.$asset_destination, 'green');
		}
		else
		{
			self::write("\t".'Error: Failed to make writable: '.$asset_destination, 'red');
			self::exit_script();
		}
	}

	/**
	 * Writes the output either to the CLI or to a log stack
	 */
	public static function write()
	{
		if (static::$is_cli)
		{
			call_user_func_array(array("\\Cli", 'write'), func_get_args());
		}
		else
		{
			static::$writes[] = func_get_args();
		}
	}

	/**
	 * Break on error
	 */
	public static function exit_script()
	{
		self::write("\t".'Script failed, please fix the above errors or initialize manually.', 'red');
		if (static::$is_cli)
		{
			exit;
		}
	}
}