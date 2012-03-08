<?php

namespace Formloader;

/**
 * Javascript Controller, for bootstrapping Backbone Models
 *
 * @package   Formloader
 * @version   1.0
 * @author    Tim Griesser
 * @license   MIT License
 * @copyright 2012 Tim Griesser
 * @link      https://tgriesser.com
 */
class Controller_Js extends \Controller {
	
	protected static $_defaults;

	/**
	 * Grab the auto-fills as necessary depending on the page we're on and
	 * renders the correct javascript dynamically
	 * @param string {forms|fieldset|fields|actions}
	 */
	public function router($method, $_id = null)
	{
		// This class only responds with JS
		$this->response->set_header('Content-Type', 'application/javascript; charset=utf-8');
		
		// We're just looking for the attributes of a single field...
		if ( ! empty($_id))
		{
			$item = Formloader_Fs::fetch_item($_id[0], $_id[1]);
			
			$backbone = $item + array(
				'_id'  => $_id[0],
				'label' => $item['group'].'.'.$item['name'],
			) + \Arr::filter_keys($item, array('validation', 'fields'));
			
			$this->response->body = json_encode($backbone);
		}
		else
		{
			// If the item isn't set in the query, then we're looking for a collection
			if ( ! \Input::get('item'))
			{
				$this->response->body = json_encode($this->get_type($method));
			}
			else
			{
				$sets = array();
				switch ($method)
				{
					case "forms":
					  // The order here matters, the fields must exist for the fieldset-sub-fields to bind them
						$sets = array('fields', 'fieldsets', 'actions');
					break;
					case "fieldsets":
						$sets = array('fields', 'actions');
					case "fields":
						$sets = array('fields', 'validations');
					break;
				}

				$js_view = PKGPATH.'formloader/modules/formloader/views/js.php.js';

				$body = \View::forge($js_view);
				$data = '';

				if ( ! empty($sets))
				{
					foreach ($sets as $set)
					{
						$init  = \Input::get('item') !== 'create' ? Formloader_Fs::fetch_item(\Input::get('item'), $method) : false;
						$data .= $this->backbone_autofill($set, $init);
					}

					$body->set(array(
						'init' => $data, 
						'use'  => json_encode($sets)
					), null, false);
				}

				$this->response->body = $body;
			}
		}
		
		return $this->response;
	}
	
	/**
	 * Backbone Autofill
	 * @param type
	 */
	public function backbone_autofill($type, $init = false)
	{
		$data = array(
			'return' => json_encode($this->get_type($type)),
			'init'   => ($init !== false ? json_encode(\Arr::get($init, $type, array())) : '[]')
		) + $this->render_collection($type);
		
		return \View::forge('js/init', $data, false);
	}
	
	/**
	 * Render the collection by the $name
	 * @param string... the collection we're rendering
	 */
	protected function render_collection($type)
	{
		$data = array(
			'collection'  => $type,
			'var_name'    => 'obj_' . $type,
			'jquery_elem' => "#$type"
		);
		
		$data['sub_call'] =  (in_array($type, array('fields', 'fieldsets', 'validations')) ? \View::forge("js/$type")->render() : '');

		return $data;
	}
	
	/**
	 * Returns an array of models for the Backbone
	 * @param string  {forms|fieldsets|fields|actions|validation}
	 */
	public function get_type($type)
	{
		$return = array();
		$data = Formloader_Fs::fetch_type($type);

		if ( ! empty($data))
		{
			// Loop through each group/item combination
			foreach ($data as $group => $items)
			{
				// In case there is a file with no return or no array items
				if (empty($items) or ! is_array($items) or in_array($group, \Config::get('formbuilder.ignored_groups')))
				{
					continue;
				}

				foreach ($items as $name => $vals)
				{
					$return_arr = array(
						'_id'   => 'fs-'.$group.'-'.$name,
						'label' => $group.'.'.$name,
						'group' => $group,
						'name'  => $name,
					) + \Arr::filter_keys($vals, array('validations', 'fields', 'prompt', 'clicktip'));

					$return[] = $return_arr;
				}
			}
		}
		
		return $return;
	}

}