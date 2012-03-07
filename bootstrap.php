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
 */
Config::load('formloader', true);

try
{
	Package::loaded('parser') or Package::load('parser');
}
catch (\PackageNotFoundException $e)
{
	echo "The Parser package is a dependency of this package, please make sure it is in the package directory!";
	exit;
}

Autoloader::add_core_namespace('Formloader');
Autoloader::add_classes(array(
	// Formloader
	'Formloader\\Formloader'          => __DIR__.'/classes/formloader.php',

	// Formloader Types
	'Formloader\\Formloader_Forms'     => __DIR__.'/classes/formloader/forms.php',
	'Formloader\\Formloader_Fields'    => __DIR__.'/classes/formloader/fields.php',
	'Formloader\\Formloader_Fieldsets' => __DIR__.'/classes/formloader/fieldsets.php',
	'Formloader\\Formloader_Actions'   => __DIR__.'/classes/formloader/actions.php',

	// Formloader Components
	'Formloader\\Formloader_Fs'       => __DIR__.'/classes/formloader/fs.php',
	'Formloader\\Formloader_Bridge'   => __DIR__.'/classes/formloader/bridge.php',

	// Standard exception thrown by the class
	'Formloader\\FormloaderException' => __DIR__.'/classes/formloader.php',
));

// Add this module to the whitelisted classes, so the form output actually shows up right!
$whitelist = Config::get('security.whitelisted_classes');
$whitelist[] = 'Formloader\\Formloader';
Config::set('security.whitelisted_classes', $whitelist);

// Determine whether the backend module is enabled
Config::get('formloader.builder.enabled');

/* End of file bootstrap.php */