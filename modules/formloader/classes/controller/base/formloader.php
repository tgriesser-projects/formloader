<?php

namespace Formloader;

/**
 * Part of the Formloader package for Fuel
 *
 * @package    Formloader
 * @subpackage Module
 * @version    1.0
 * @author     Tim Griesser
 * @license    MIT License
 * @copyright  2012 Tim Griesser
 * @link       http://tgriesser.com
 */
class Controller_Base_Formloader extends \Controller_Template
{
	protected static $asset_destination;
	
	/**
	 * Make sure we have all of the necessary materials
	 * to load the backend of the Formbuilder...
	 */
	public static function _init()
	{
		static::$asset_destination = DOCROOT . \Config::get('formloader.builder.asset_destination');

		if (is_dir(static::$asset_destination))
		{
			\Asset::add_path(\Config::get('formloader.builder.asset_destination'));
			$paths = \Config::get('formloader.builder.asset_subpaths', false);

			if ( ! empty($paths))
			{
				foreach ($paths as $path)
				{
					\Asset::add_path($path);
				}
			}
		}
	}

	/**
	 * Set the defaults for the template body
	 */
	public function before()
	{
		// Show the error for people that haven't run the "oil refine formloader" yet
		if ( ! is_dir(static::$asset_destination))
		{
			echo \View::forge('errors/assets', array(
				'asset_source'      => realpath(\Config::get('formloader.builder.asset_source')),
				'asset_destination' => static::$asset_destination
			));
			die;
		}
		
		$this->template = \View::forge('template');
		$this->template->set(array(
		  'header'  => \View::forge('includes/header'),
		  'formloader_alert' => array(),
		  'title'   => '',
		  'content' => '',
		  'navbar'  => (\Input::get('ref') === 'popup' ? '' : \ViewModel::forge('includes/navbar')),
		  'github'  => (\Input::get('ref') !== 'popup' ? : false),
		  'footer'  => \View::forge('includes/footer')
		));
		
		return \Controller::before();
	}

	/**
	 * Centralize all flash data...
	 * @param object|null $response
	 */
	public function after($response)
	{
		$flash = \Session::get_flash('formloader_alert');
		
		if ( ! is_null($flash))
		{
			$this->template->set('formloader_alert', $flash, false);
		}

		return parent::after($response);
	}

	/**
	 * Alias the index to the list
	 */
	public function action_index()
	{
		$this->action_list();
	}

	/**
	 * Settings for the Formbuilder module...
	 * @param the Settings we're updating
	 */
	public function action_settings()
	{
		$ignored_groups = Formloader::forge('formloader', 'ignored_groups');
		$ignored_groups->values(\Config::get('formbuilder.ignored_groups'));
		$this->template->content = \View::forge('settings', array('ignored_groups' => $ignored_groups));
	}
	
	/**
	 * Edits a specific item (forms/fieldsets/fields/actions)
	 * @param array|null
	 */	
	public function edit($id, $type)
	{
		// Grab the item by "id"
		$item = Formloader_Fs::fetch_item($id, $type);

		// Set the unhide variable, so we can reduce the form size
		\View::set_global('unhide', json_encode(self::array_key_collapse($item)), false);

		if ( ! empty($item))
		{
			$this->template->title = 'Edit '.ucfirst($type).': '.$item['name'];
			
			// Split up the associative array objects from the int keys
			$item['options'] = isset($item['options']) ? json_encode($item['options']) : array();
			
			// Grab the data-attribute-items
			if (isset($item['attributes']))
			{
				$item['attributes']['data'] = isset($item['attributes']['data']) ? json_encode($item['attributes']['data']) : array();
			}
			
			// The hidden variable determines whether this is a popup
			$form = Formloader::forge('formloader', $type)->values($item)
				->hidden('hidden_vars[get][]', \Input::get('ref'));

			$this->template->content = $form;
		}
		else
		{
			$this->template->title = 'Error';
			$this->template->content = 'Error finding ' . $type;
		}
	}

	/**
	 * Checks if the array's keys/sub-keys aren't (int)...
	 * if so it adds them to a stack of collapsed keys
	 * @param array
	 */
	public function array_key_collapse($array)
	{
		$stack = array();
		$depth = array();
		$collapse = function($val, $key, $func) use(&$stack, &$depth)
		{
			array_push($depth, $key);
			if (is_array($val))
			{
				$stack[] = implode('.', $depth);
				array_walk($val, $func, $func);
			}
			else
			{
				if ( ! is_int($key))
				{
					$stack[] = implode('.', $depth);
				}
			}
			array_pop($depth);
		};
		
		array_walk($array, $collapse, $collapse);
		
		return $stack;
	}
	
}