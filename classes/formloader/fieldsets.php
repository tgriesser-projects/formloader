<?php

namespace Formloader;

/**
 * Part of the Formloader package for Fuel
 *
 * @package   Formloader
 * @version   1.1
 * @author    Tim Griesser
 * @license   MIT License
 * @copyright 2012 Tim Griesser
 * @link      http://formloader.tgriesser.com
 */
class Formloader_Fieldsets extends Formloader_Bridge
{
	/**
	 * The defaults array set in the _init()
	 * @var array
	 */
	public static $_defaults;

	/**
	 * Called by fuelPHP when the class is first initialized
	 * ...almost like a static constructor
	 */
	public static function _init()
	{
		/**
		 * The Loopforge array for composing a "Fieldset"
		 * @var array
		 */
		static::$_defaults = array(

			/**
			 * Determines the type of Formloader object we're inheriting from
			 * in the Formloader_Bridge class
			 */
			'object_type' => 'fieldsets',
			
			/**
			 * Required name of the group (used to namespace the form items)
			 * @var String
			 * @throws FormloaderException
			 */			
			'group'  => function()
			{
				throw new FormloaderException('A group is needed in order to deal with this properly');
			},
			
			/**
			 * Required name of the action
			 * @var String
			 * @throws FormloaderException
			 */
			'name'   => function()
			{
				throw new FormloaderException('A name is needed in order to deal with this properly');
			},

			/**
			 * The _id of the form item... prefixed with "fs-" so that we can easily add
			 * mongoDB form items and be able to differentiate between the two
			 * @param  array   $f - current field array
			 * @return string  the _id of the field
			 */
			'_id'        => function($f)
			{
				return 'fs-'. $f['group'] . '-'.$f['name'];
			},

			/**
			 * All attributes used in the fieldset tag
			 * @var array
			 */
			'attributes' => array(

				/**
				 * Id tag for the fieldset
			 	 * @param array  - reference to the current fieldset object
				 * @return string name
				 */
				'id'    => function($f)
				{
					return Formloader_Bridge::unique_id($f);
				},

				/**
				 * Class attribute for the fieldset html
				 * @var string
				 */
				'class' => '',
				
				/**
				 * The name of the form that we're using, will be modified below to reflect depth
				 * @param array - the current field array
				 * @return string
				 */
				'name' => function($f)
				{
					return $f['name'];
				},
				
				/**
				 * Style attribute for the fieldset (don't use too many inline styles)
				 * @var string
				 */
				'style' => '',
				
				/**
				 * Array of individual data-attribute pairs
				 * (All data-attr will be filtered below)
				 * @var array
				 */
				'data'  => array()
			),

			/**
			 * Filters all data- attributes
			 * @param  array - reference to the field object
			 * @return string  __remove__
			 */
			'_data' => function(&$f)
			{
				$f['attributes']['data'] = Formloader_Bridge::data_filter($f);
				return '__remove__';
			},
			
			'fieldset_open' => function(&$f)
			{
				return \Form::fieldset_open(array_filter($f['attributes']));
			},
			
			'fieldset_close' => '</fieldset>',

			/**
			 * The 'legend' tag text for this fieldset
			 * @var bool
			 */
			'legend'     => '',
			
			/**
			 * Determines whether we use the legend or not
			 * @var bool
			 */
			'use_legend' => function ($f)
			{
				return ! empty($f['legend']) ? true : false;
			},
			
			/**
			 * List of all fields contained in this fieldset
			 * @var array
			 */
			'fields'     => array(),
			
			/**
			 * Processes each of the fields and sets the result
			 * @param array  - reference to the field object
			 * @return string  __remove__
			 */
			'_fields'  => function(&$f)
			{
				$f['fields'] = call_user_func("\\Formloader_Fields::_process_items", $f);
				return '__remove__';				
			},

			/**
			 * Default template for the action
			 * @var string
			 */
			'template'       => 'default.mustache',

			/**
			 * Resolves the template directory for the action
			 * @param  array $f - current action array
			 * @return string
			 */
			'template_dir'   => function($f)
			{
				return Formloader_Bridge::template_directory($f);
			},

			/**
			 * Path to the template relative to the "modules/formloader/templates" directory
			 * @param  array $f - current action array
			 * @return string
			 */			
			'template_path'  => function($f)
			{
				return $f['template_dir'].DS.$f['template'];
			},

			/**
			 * Output HTML for the action
			 * @param array
			 * @return string  rendered \View object
			 */
			'template_html' => function($f)
			{
				return Formloader_Mustache::forge($f['template_path'], $f, false)->render();
			}
		);
	}
}