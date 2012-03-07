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
class Controller_Fields extends Controller_Base_Formloader
{
	/**
	 * Lists all of the items for a specific category (forms/fieldsets/fields/actions)
	 * @param array|null
	 */
	public function action_list()
	{
		$this->template->title   = 'All Fields:';
		$this->template->content = \Formloader_Scaffold::forge('fields');
	}
	
	/**
	 * Creates a specific item (forms/fieldsets/fields/actions)
	 * @param array|null
	 */
	public function action_create()
	{
		$this->template->title = 'Create new Field:';
		$this->template->content = Formloader::forge('formloader', 'fields')
			->hidden('hidden_vars[get][]', \Input::get('ref'));
	}
	
	/**
	 * Edits a specific field
	 * @param string   id that we're editing
	 */
	public function action_edit($id)
	{
		parent::edit($id, 'fields');
	}
}