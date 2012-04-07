<?php

namespace Formloader;

/**
 * API Controller for the Formloader CRUD
 * 
 * @package   Formloader
 * @version   1.1
 * @author    Tim Griesser
 * @license   MIT License
 * @copyright 2012 Tim Griesser
 * @link      http://formloader.tgriesser.com
 */
class Controller_Api extends \Controller_Rest
{
	/**
	 * Only field types which are able to have 'options' associated with them
	 * @var array
	 */
	protected $dropdown_types = array('dropdown', 'checkboxes', 'radios', 'select');

	/**
	 * Surrounds the router in a try-catch, to help with correct output notifications and ajax
	 * @param string
	 * @param array
	 */
	public function router($resource, array $arguments)
	{
		try
		{
			parent::router($resource, $arguments);
		}
		catch (\Exception $e)
		{
			throw new FormloaderException($e->getMessage());
		}
	}
		
	/**
	 * Saves the form submission...also compiles & saves output
	 * if this is a form or if there are forms affected by the save
	 * @param string   - form/field/fieldset/action
	 */
	public function post_save($type)
	{
		$post   = $this->sort_tags(\Input::post());
		$hidden = \Arr::get($post, 'hidden_vars', array());

		// Ensures that the post options are processed only if this is a dropdown (multi-select coming soon)
		if (isset($post['options']) and ! empty($post['options']))
		{
			$this->prep_opts($post);
		}
		if (isset($post['attributes']['data']) and ! empty($post['attributes']['data']))
		{
			$this->prep_set($post['attributes']['data']);
		}

		// Unset unnecessary values for form processing...
		unset($post['submit']);
		unset($post['hidden_vars']);
		unset($post['api_action']);

		$item = Formloader_Fs::fetch_group_type($post['group'], $type);
		$item[$post['name']] = \Arr::filter_keys($post, array('name', 'group'), true);

		// Determines if we created or updated this item...
		$create = Formloader_Fs::save_group_type($post['group'], $type, $item);

		// Check if we're compiling the form...
		if ($type === 'forms')
		{
			$output = $this->compile($type, 'process_arrays', $this->sort_tags($post));
			Formloader_Fs::save_item('output', $post['group'], "{$post['name']}.mustache", $output['template_html']);
			
			if ( ! empty($output['obj']['validation']))
			{
				// Save the attribute array for the form
				$output['obj']['validation'] = json_decode(str_replace('fuel.', '', json_encode($output['obj']['validation'])));

				foreach ($output['obj']['validation'] as &$arr)
				{
					$arr[1] = substr($arr[1], -1) === ':' ? substr($arr[1], 0, -1) : $arr[1];
					$arr[2] = implode('|', $arr[2]);
				}
				unset($arr);
			}

			$items = Formloader_Fs::prep_array_output('attributes', $post['group'], array_filter($output['obj']['form_packager']));
			Formloader_Fs::save_item('output', $post['group'], "{$post['name']}_attr.php", $items);
		}

		$message = 'Form: "'.$post['name'].'" in Group: "'.$post['group'].'" has been '.($create ? 'created' : 'updated').'!';

		// See if we need to kill the window
		$kill = isset($hidden['get']) and in_array('popup', $hidden['get']) ? : false;
		
		$url = $kill ? null : $type.'/fs-'.$post['group'].'-'.$post['name'];
		
		return $this->redirect_response(array(
			'message' => $message,
			'type'    => 'success'
		), 200, $url, $kill);
	}

	/**
	 * Allows us to easily preview the submission in a new window
	 * outputting the rendered form and ending script execution
	 * @param the type of item that we're previewing {forms|fieldsets|fields|actions}
	 */
	public function post_preview($type)
	{
		$post = \Input::post();

		// Ensures that the post options are processed only if this is a dropdown (multi-select coming soon)
		if (isset($post['options']) and ! empty($post['options']))
		{
			$this->prep_opts($post);
		}
		if (isset($post['attributes']['data']) and ! empty($post['attributes']['data']))
		{
			$this->prep_set($post['attributes']['data']);
		}

		try
		{
			$obj = $this->compile($type, 'process_arrays', $this->sort_tags($post));
		}
		catch (\Exception $e)
		{
			$obj['template_html'] = $e->getMessage();
		}

		// So it looks good when we're rendering...
		if ($type !== 'forms')
		{
			$obj['template_html'] = '<form class='.\Config::get('formloader.builder.preview_class').'>' 
										. $obj['template_html'] . '</form>';
		}

		$output = Formloader_Mustache::parser('preview')
			->render($obj['template_html']);
		echo preg_replace("#<script(.*?)<\/script>#is", '', $output);
		exit;		
	}

	/**
	 * AJAX only - deletes individual items from the Formloader
	 * @param bool whether to refresh all dependent elements on deletion
	 */
	public function post_delete($id)
	{
		# Todo... figure out unsetting
	}
	
	/**
	 * Gets messages after windows are closed on modal updates...
	 */
	public function get_messages()
	{
		# Todo... figure out alerts here
	}

	/**
	 * Easily handle the response for all Formbuilder methods...
	 * @param string  the message output by the formbuilder
	 * @param string  whether this is a success/error/warning/info
	 * @param array   hidden variables?
	 */
	private function redirect_response($message, $code = 200, $url = '', $kill = false)
	{
		if ( ! \Input::is_ajax())
		{
			\Session::set_flash('formloader_alert', $message);
			
			if ($kill)
			{
				echo \View::forge('includes/kill_window');
				exit;
			}
			else
			{
				\Response::redirect('formloader/'.$url);
			}			
		}
		else
		{
			return $this->response($message, $code);
		}
	}

	/**
	 * Preps the opts, making sure the type is a dropdown...
	 * @param &array - post array we're dealing with
	 */
	private function prep_opts(&$post)
	{
		if (isset($post['attributes']['type']) and in_array($post['attributes']['type'], $this->dropdown_types))
		{
			$this->prep_set($post['options']);
		}
		else 
		{
			unset($post['options']);
		}	
	}

	/**
	 * Constructs the options array properly based on the key/value pairs
	 * @param &array - item we're setting the k=>v pairs on
	 */
	private function prep_set(&$post_item)
	{
		$arr = array();

		foreach ($post_item['key'] as $k => $key_val)
		{
			if ( ! empty($key_val))
			{
				$arr[$key_val] = isset($post_item['value'][$k]) ? $post_item['value'][$k] : "";	
			}
		}
		$post_item = $arr;
	}

	/**
	 * Checks the validity of the tags in the tagit array
	 * against the json submitted by the backbone...
	 * maintaining the order from the sortable
	 * @param array
	 */
	private function sort_tags($arr)
	{
		if (strpos($arr['name'], '-') !== false or strpos($arr['group'], '-') !== false)
		{
			throw new FormloaderException('Dashes are not allowed in item names, only underscores &amp; alpha-numeric.');
		}

		foreach ($arr as $k => $v)
		{
			if ($k === 'tagit' and ! empty($v))
			{
				foreach ($v as $type => $items)
				{
					if (isset($arr["tagit_{$type}"]) and ! empty($arr["tagit_{$type}"]))
					{
						$valid_keys = array();
						array_walk($arr["tagit_$type"], function(&$v) use(&$valid_keys, $type)
						{
							$v = json_decode($v);
							if (isset($v->_id))
							{
								$valid_keys[] = $v->_id . (($type === 'validations' and $v->prompt !== false) ? "[$v->subVal]" : '');
							}
						});
					}
					unset($arr["tagit_$type"]);
					foreach ($items as $k => $item)
					{
 						if ( ! in_array($item, $valid_keys))
						{
							unset($items[$k]);
						}
					}
					$arr[$type] = $items;
				}
				unset($arr['tagit']);
			}
		}

		// Pair down the posted object so we don't have blank attributes all over the place...
		$filtered = \Loopforge::array_filter_recursive($arr);

		// Ensures we don't lose the blank data- items
		if ( ! empty($arr['attributes']['data']))
		{
			\Arr::set($filtered, 'attributes.data', $arr['attributes']['data']);
		}

		// Ensures we don't lose the blank values associated with the options array...
		if ( ! empty($arr['options']))
		{
			\Arr::set($filtered, 'options', $arr['options']);
		}

		return $filtered;
	}

	/**
	 * Compiles the HTML for the Formloader
	 * @param string type   {Form|Fieldset|Field|Action}
	 * @param string method 
	 */
	public function compile($type, $act, $arg)
	{
		$obj = call_user_func("Formloader_".ucfirst($type)."::$act", $arg);
		return array(
			'template_html' => str_replace(
				array('{%%','%%}', '{%','%}'), 
				array('{{{','}}}', '{{','}}'), 
				$obj['template_html']
			),
			'obj' => $obj
		);
	}
	
}