<?php

namespace Formloader;

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
class Formloader_Mustache extends \View
{
	protected static $_parser;
	
	/**
	 * Called by fuelPHP when the class is first initialized
	 * ...almost like a static constructor
	 */
	public static function _init()
	{
		if ( ! class_exists('Mustache'))
		{
			if ( ! include \Config::get('formloader.mustache_path'))
			{
				throw new FormloaderException('Mustache templating is required, please set the directory correctly in the config');
			}
		}
	}

	/**
	 * Returns the Parser lib object
	 *
	 * @return  Mustache
	 */
	public static function parser($name = 'default')
	{
		if ( ! empty(static::$_parser[$name]))
		{
			return static::$_parser[$name];
		}

		static::$_parser[$name] = new \Mustache;

		return static::$_parser[$name];
	}

	/**
	 * Processes the particular file
	 */
	protected function process_file($file_override = false)
	{
		$file = $file_override ?: $this->file_name;
		$data = $this->get_data();

		try
		{
			return static::parser()->render(file_get_contents($file), $data);
		}
		catch (\Exception $e)
		{
			// Delete the output buffer & re-throw the exception
			ob_end_clean();
			throw $e;
		}
	}

	/**
	 * Returns a new View object. If you do not define the "file" parameter,
	 * you must call [static::set_filename].
	 *
	 *     $view = View::forge($file);
	 *
	 * @param  string  view filename
	 * @param  array   array of values
	 * @return  View
	 */
	public static function forge($file = null, $data = null, $auto_encode = true)
	{
		// Get the extension to pull off the end
		$extension = pathinfo($file, PATHINFO_EXTENSION);
		
		// Make this an absolute path if it isn't one already...
		if ($file[0] !== '/' and $file[1] !== ':')
		{
			$file = \Config::get('formloader.output_path').'templates/' . $file;
		}

		// Create a new view
		$view = new \Formloader_Mustache(null, $data, $auto_encode);

		// Set extension when given
		$extension and $view->extension = $extension;

		// Load the view file
		$view->set_filename($file);

		return $view;
	}
}