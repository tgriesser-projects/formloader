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
 */
Autoloader::add_core_namespace('Formloader');
Autoloader::add_classes(array(
	// Formloader
	'Formloader\\Formloader'           => __DIR__.'/classes/formloader.php',

	// Formloader Types
	'Formloader\\Formloader_Forms'     => __DIR__.'/classes/formloader/forms.php',
	'Formloader\\Formloader_Fields'    => __DIR__.'/classes/formloader/fields.php',
	'Formloader\\Formloader_Fieldsets' => __DIR__.'/classes/formloader/fieldsets.php',
	'Formloader\\Formloader_Actions'   => __DIR__.'/classes/formloader/actions.php',

	// Formloader Components
	'Formloader\\Formloader_Fs'       => __DIR__.'/classes/formloader/fs.php',
	'Formloader\\Formloader_Bridge'   => __DIR__.'/classes/formloader/bridge.php',
	'Formloader\\Formloader_Mustache' => __DIR__.'/classes/formloader/mustache.php',
	
	// Standard exception thrown by the class
	'Formloader\\FormloaderException' => __DIR__.'/classes/formloader.php',
));

// Load the formloader config
Config::load('formloader', true);

// Add this module to the whitelisted classes, so the form output actually shows up right!
$whitelist = Config::get('security.whitelisted_classes');
$whitelist[] = 'Formloader\\Formloader';
Config::set('security.whitelisted_classes', $whitelist);

// Decide whether to load the module
Config::get('formloader.builder.enabled');

/* End of file bootstrap.php */