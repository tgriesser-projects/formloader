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
 * @link       http://formloader.tgriesser.com
 */
class Controller_Base extends \Controller_Template
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
		if ( ! is_dir(static::$asset_destination) or ! is_dir(\Config::get('formloader.output_path')))
		{
			echo \View::forge('errors/assets');
			exit;
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
	 * Lists all of the items for a specific category (forms/fieldsets/fields/actions)
	 * @param array|null
	 */
	public function action_list($type)
	{
		$this->template->title = 'All ' . $type;
		$this->template->content = \Formloader_Scaffold::forge($type);
	}
	
	/**
	 * Creates an action
	 * @param array|null
	 */
	public function action_create($type)
	{
		$this->template->title = 'Create new ' . substr($type, 0, -1);

		// Create the appropriate form depending on the page we're on...
		$this->template->content = Formloader::forge('formloader', $type, false)
			->set('route_success', 'HMVC::formloader/api/save/' . $type)
			->hidden('hidden_vars[get][]', \Input::get('ref'))
			->listen();
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
	 * Updates the formloader with changes to the git repo
	 * 
	 */
	public function action_update()
	{
		if (\Input::method() === 'POST')
		{
			$content = <<<HTML
<style type="text/css" media="screen">
	li.green{
		color: green;
	}
	li.yellow{
		color:orange;
	}
	li.red{
		color:red;
	}
</style>
HTML;
			$content .= '<ul>';
			foreach (Formloader_Migration::migrate_app() as $item)
			{
				$content .= '<li class="'. \Arr::get($item, '1', '') .'">' . $item[0] . '</li>';
			}
			$content .= '</ul>';

			$this->template->set('content', $content, false);
		}
		else
		{
			$this->template->content = \View::forge('update');
		}
	}

	/**
	 * Edits a specific item (forms/fieldsets/fields/actions)
	 * @param array|null
	 */	
	public function action_edit($type, $id)
	{
		// Grab the item by "id"
		$item = Formloader_Fs::fetch_item($id, $type);

		// Set the unhide variable, so we can reduce the form size
		\View::set_global('unhide', json_encode(\Loopforge::array_key_collapse($item)), false);

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
			
			$this->template->content = Formloader::forge('formloader', $type, false)
				->values($item)
				->set('route_success', 'HMVC::formloader/api/save/' . $type)
				->hidden('hidden_vars[get][]', \Input::get('ref')) // The hidden variable determines whether this is a popup
				->listen();
		}
		else
		{
			$this->template->title = 'Error';
			$this->template->content = 'Error finding ' . $type;
		}
	}
	
}