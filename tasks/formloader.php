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
		\Formloader_Migration::migrate_cli($public_path);
	}
}