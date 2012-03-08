<?php

namespace Formloader;

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
class Formloader_Mustache extends \View
{
	protected static $_parser;

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
}