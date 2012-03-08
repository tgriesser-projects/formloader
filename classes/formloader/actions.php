<?php

namespace Formloader;

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
class Formloader_Actions extends Formloader_Bridge
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
		 * The Loopforge array for composing an "Action" (a button to perform an action on the form)
		 * @var array
		 */
		static::$_defaults = array(

			/**
			 * Determines the type of Formloader object we're inheriting from
			 * in the Formloader_Bridge class
			 */
			'object_type' => 'actions',

			/**
			 * Required name of the group (used to namespace the form items)
			 * @var String
			 * @throws \FormloaderException
			 */
			'group'  => function()
			{
				throw new FormloaderException('A group is needed in order to deal with this properly');
			},

			/**
			 * Required name of the action
			 * @var String
			 * @throws \FormloaderException
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
				return 'fs-'.$f['group'].'-'.$f['name'];
			},

			/**
			 * The attributes array is passed to array_filter 
			 * and used to set the attributes
			 * @var array
			 */
			'attributes' => array(

				/**
				 * Id tag for the action
			 	 * @param array  - reference to the current action object
				 * @return string name
				 */
				'id'    => function($f)
				{
					return Formloader_Bridge::unique_id($f);
				},
				
				/**
				 * Button "type"
				 * @param array $f - current action array
				 * @return string
				 */
				'type'   => function ($f)
				{
					return $f['name'] === 'submit' ? 'submit' : 'button';
				},
				
				/**
				 * String of the current class
				 * @param String
				 */
				'class'  => '',
				
				/**
				 * The "name" attribute for the button...typically an empty value
				 * @var string
				 */
				'name' => '',
				
				/**
				 * The "value" of the button (text displayed on the button)
				 * if empty, it will be constructed from the formloader name of the button
				 * @var string
				 */
				'value' => '',
				
				/**
				 * Array of key => value pairs, each key will be filtered below,
				 * prefixed with 'data-' and added to the attributes...
				 * @var array
				 */
				'data'  => array()  // All data-attr will be filtered below				
			),

			/**
			 * Ensures that every button has a "btn" class
			 * @param  Reference to the Action object
			 * @return string  __remove__
			 */
			'_class' => function (&$f)
			{
				$f['attributes']['class'] .= ( ! empty($f['attributes']['class']) ? ' btn' : 'btn');
				return '__remove__';
			},
			
			/**
			 * Filters all data- attributes
			 * @param  Reference to the Action object
			 * @return string  __remove__
			 */
			'_data' => function(&$f)
			{
				return Formloader_Bridge::data_filter($f);
			},

			/**
			 * Required name of the action
			 * @param array $f - current action array
			 * @return string
			 */
			'_value' => function(&$f)
			{
				if (empty($f['attributes']['value']))
				{
					$f['attributes']['value'] = ucwords(str_replace('_', ' ', $f['name']));
				}
				return '__remove__';
			},
			
			/**
			 * The action button... we can override this to directly set the action's HTML
			 * @param  array $f - current action array
			 * @return string
			 */
			'action' => function ($f)
			{
				return \Form::button(array_filter($f['attributes']));
			},
			
			/**
			 * Default view for the action
			 * @var string
			 */
			'view'       => 'default.mustache',

			/**
			 * Resolves the view directory for the action
			 * @param  array $f - current action array
			 * @return string
			 */
			'view_dir'   => function($f)
			{
				return Formloader_Bridge::view_directory($f);
			},
			
			/**
			 * Path to the view relative to the "formloader/views" directory
			 * @param  array $f - current action array
			 * @return string
			 */			
			'view_path'  => function($f)
			{
				return $f['view_dir'].DS.$f['view'];
			},
			
			/**
			 * Output HTML for the action
			 * @param array
			 * @return string  rendered \View object
			 */
			'view_html' => function($f)
			{
				return \View::forge($f['view_path'], $f, false)->render();
			}
		);
	}
}