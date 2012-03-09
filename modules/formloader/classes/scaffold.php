<?php

namespace Formloader;

/**
 * Scaffold Class, a helper for the Views
 * Part of the Formloader package for Fuel
 *
 * @package   Formloader
 * @version   1.1
 * @author    Tim Griesser
 * @license   MIT License
 * @copyright 2012 Tim Griesser
 * @link      http://formloader.tgriesser.com
 */
class Formloader_Scaffold
{
	protected $html = false;
	public static $_defaults;

	/**
	 * Renders the view if we haven't set the HTML output yet
	 * @return string  view html
	 */
	public function __toString()
	{
		$this->html === false and $this->render();
		return $this->html;
	}
	
	/**
	 * Creates a new scaffold instance and sets the type
	 * @param string - {forms|fields|fieldsets|actions}
	 */
	public static function forge($type)
	{
		$self = new static();
		$self->type = $type;
		return $self;
	}
	
	/**
	 * Renders the output for the Scaffold
	 */
	public function render()
	{
		// Ensure it's only rendered once...
		if ( $this->html !== false)
		{
			return $this->html;
		}
		
		$scaffold = array(
			'singular' => ucfirst(substr($this->type, 0, -1)),
		);
		
		$scaffold['headings'] = array('Group', $scaffold['singular'] . ' Name', 'Edit');
		$scaffold['items']    = Formloader_Fs::fetch_type($this->type);
		
		$this->thead($scaffold);
		$this->tbody($scaffold);
		$this->html($scaffold);

		$this->html = $scaffold['html'];
	}
	
	/**
	 * Renders the headings for the scaffold
	 * @param array
	 */
	public function thead(&$scaffold)
	{
		$html = '<tr>' . PHP_EOL;
		foreach ($scaffold['headings'] as $heading)
		{
				$html .= "\t" . '<th>' . $heading . '</th>' . PHP_EOL;
		}
		$html .= '</tr>' . PHP_EOL;
		$scaffold['thead'] = $html;
	}
	
	/**
	 * Renders the rows for the scaffolding
	 * @param array
	 */
	public function tbody(&$scaffold)
	{
		$html = '';
		if ( ! empty($scaffold['items']))
		{
			foreach ($scaffold['items'] as $group_name => $group_items)
			{
				if ( ! in_array($group_name, \Config::get('formbuilder.ignored_groups')))
				{
					if ( ! empty($group_items) and is_array($group_items))
					{
						foreach ($group_items as $form_name => $form)
						{
							$_id  = 'fs-'.$group_name.'-'.$form_name;
							$uri  = \Uri::create('formloader/'.strtolower($this->type).'/'.$_id);
							$html .= <<<ROW
<tr>
	<td>$group_name</td>
	<td>$form_name</td>
	<td><a class="btn" href="$uri">Edit</a></td>
	<!--<td><a class="btn btn-danger" data-formloader-delete="delete">Delete</a></td>-->
</tr>
ROW;
						}
					}
				}
			}
		}
		$scaffold['tbody'] = $html;
	}
	
	/**
	 * Generates all the output for the scaffold
	 * @param array
	 */
	public static function html(&$scaffold)
	{
		$html  = '<table class="table">';
		$html .= '<thead>' . $scaffold['thead'] . '</thead>';
		$html .= '<tbody>' . $scaffold['tbody'] . '</tbody>';
		$html .= '</table>';
		$scaffold['html'] = $html;
	}

	/**
	 * Returns an array of all of the ignored groups on a "GET"
	 * on a "POST" it updates the ignored groups
	 */
	public static function ignored_groups()
	{
		if (\Input::method() === "POST" and \Input::post('api_action', 'formloader.ignored_groups'))
		{
			$path = \Config::get('formloader.output_path').'config/formbuilder.php';
			\Config::save($path, array(
				'ignored_groups' => array_keys(\Input::post('ignored_groups', array()))
			));
			\Session::set_flash('formloader_alert', array(
				'message' => 'The forms displayed in the Formbuilder was updated!',
				'type' => 'success'
			));
			return \Response::redirect(\Uri::main());
		}
		else
		{
			$groups = \Config::get('formbuilder.ignored_groups');
			$return = array();
			if ($dirs = \File::read_dir(\Config::get('formloader.output_path').'forms'))
			{
				foreach ($dirs as $k => $v)
				{
					$k = substr($k, 0, -1);
					if ($k !== 'fuel')
					{
						$return[] = array(
							'name'    => $k,
							'label'   => $k,
							'checked' => in_array($k, $groups)
						);
					}
				}
			}
			return $return;
		}
	}
}