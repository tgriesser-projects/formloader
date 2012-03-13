<?php

namespace Formloader;

/**
 * Standard Formloader Exception
 */
class FormloaderException extends \FuelException {}

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
class Formloader
{
	/**
	 * Set these directly when creating the Formloader object
	 */
	public $action;
	public $route_success;
	public $route_error;
	
	protected $_id;
	protected $name;
	protected $group;  
	
	protected $html = false;
	protected $rendered = false;
	
	protected $selects  = array();
	protected $checks   = array();
	protected $defaults = array();
	protected $option_calls = array();
	protected $validation;
	
	protected $values   = array();
	protected $errors   = array();
	protected $validated_inputs = array();
	
	/**
	 * Load language file, config is already loaded in Bootstrap
	 */
	public static function _init()
	{
		\Lang::load('formloader', true);
	}
		
	/**
	 * Called for class rendering, whitelisted by default in bootstrap.php
	 * @return string  entire form output
	 */
	public function __toString()
	{
		return $this->render();
	}
	
	/**
	 * Handles all validation, form generation, and request
	 * @param string  group or BSON _id
	 * @param string  form name or false (if this is a database form)
	 * @return \Formloader instance
	 */
	public static function forge($group, $name = false, $listen = true)
	{
		$self = new static($group, $name);
		$listen and $self->listen();
		return $self;
	}

	/**
	 * Grab all necessary pieces for form validation & generation
	 * @param string  group or BSON _id
	 * @param string  form name or false (if this is a database form)
	 * @return \Formloader instance
	 */
	public function __construct($group, $name = false)
	{
		// This is the most likely way things are called...
		if ($name !== false)
		{
			$this->name  = $name;
			$this->group = $group;
			$this->_id   = $this->group . '.' . $this->name;

			// Check for the form & settings...
			$form = self::fetch_files($group, $name);

			if ( ! empty($form))
			{
				foreach ($form as $k => $v)
				{
					$this->$k = $v;
				}
			}
			else
			{
				throw new FormloaderException('The Group/Name combination was not found');
			}
		}
		else // If the form is false, it means we're working with an MongoId...
		{
			throw new FormloaderException('The Group/Name combination was not found');
		}
	}
		
	/**
	 * Calls the validation on the submission
	 * @param array   input that overwrites POST values
	 * @param bool    will skip validation of values it can't find or are null
	 * @return bool    true if it validates, false if it doesn't, null if there isn't a set validation
	 * @throws FormloaderException  if the validation has already been set once...
	 */
	public function validate($input = array(), $allow_partial = false)
	{
		$val = \Validation::instance($this->_id) ? : \Validation::forge($this->_id);

		if ( ! empty($this->validation))
		{
			foreach ($this->validation as $ruleset)
			{
				$val->add_field($ruleset[0], $ruleset[1], $ruleset[2]);
			}
			if ( ! $val->run($input, $allow_partial))
			{
				$this->errors = $val->error();
				return false;
			}
			$this->validated_inputs = $val->validated();
			return true;
		}
	}
	
	/**
	 * Sets any values we want in the form, before the actual render call
	 * @param array|string  either an array of values to set, or the name of the value
	 * @param string|null   if the $item isn't an array, this is the value
 	 * @return $this for method chaining
	 */
	public function values($item, $value = null)
	{
		if (is_array($item) or is_object($item))
		{
			foreach ($item as $key => $val)
			{
				$this->values[$key] = $val;
			}
		}
		else
		{
			$this->values[$item] = $value;
		}
		return $this;
	}
	
	/**
	 * Allows us to dynamically add hidden inputs to the form at runtime...
	 * @param string|Array  name of the item we're hiding, or an array of items
	 * @param Value         a non-empty value for the hidden field
	 * @return $this for method chaining
	 */
	public function hidden($name, $val = null)
	{
		if (is_array($name) and ! empty($name))
		{
			foreach ($name as $k => $v)
			{
				$this->hidden($k, $v);
			}
			return $this;
		}
		if ( ! empty($val))
		{
			$this->values['__hidden_vars'][] = array('name' => $name, 'value' => $val);
		}
		return $this;
	}

	/**
	 * Checks if the submission is "POST" 
	 * and the form's api_action matches the submission
	 * If it does then it takes the appropriate action (form rendering or the like)
	 * @return $this for method chaining
	 */
	public function listen()
	{
		if (\Input::method() === 'POST' and \Input::post('api_action') === $this->_id and \Request::main() === \Request::active())
		{
			if ($this->validate() !== false and $this->route_success)
			{
				$this->routing($this->route_success, 'success');
			}
			elseif ( ! empty($this->errors) and $this->route_error)
			{
				$this->routing($this->route_error, 'error');
			}
		}
		return $this;
	}
	
	/**
	 * Helper function for calling a specific static/HMVC function on validation success/failure
	 * @param string... path to the success/error call
	 */
	private function routing($path)
	{
		$goto = explode('::', $path);
		$args = ! empty($this->errors) ? $this->errors : $this->validated_inputs;
		
		try
		{
			switch ($goto[0])
			{
				// Typically HMVC calls will route to Rest_Controller... 
				// as this makes the most sense for responding with a form alert
				// otherwise we'll expect it redirects or exits from there...
				case "HMVC":
					$response = \Request::forge($goto[1], false)->execute();
				break;
				default:
					// Ensure the method is valid
					if (is_callable($goto))
					{
						$response = call_user_func($goto, $args);
					}
					else
					{
						throw new FormloaderException('The requested method is not routable.');
					}
				break;
			}
			
			if (is_array($response) or ! is_null($response = json_decode($response)))
			{
				\Session::set_flash('formloader_alert', $response);
			}
		}
		catch (\HttpNotFoundException $e)
		{
			\Session::set_flash('formloader_alert', array(
				'type' => 'error',
				'message' => '404 Form Target Not found...'
			));
		}
		catch (FormloaderException $e)
		{
			\Session::set_flash('formloader_alert', array(
				'type' => 'error',
				'message' => $e->getMessage(),
				'code' => '400'
			));
		}
	}

	/**
	 * Renders the form
	 * @param array 
	 * @param if this is a form submission, any of the submitted items on this form may be overwritten
	 * @return html to display
   */
	public function render($values = array(), $override_post = false)
	{
		if ($this->rendered)
		{
			return $this->rendered;
		}
		
		// Populate the dynamic multi-select items at runtime
		if ( ! empty($this->option_calls))
		{
			foreach ($this->option_calls as $field => $func)
			{
				$this->values[$field] = call_user_func($func) or array();
			}
		}
		
		$values = \Arr::merge($this->values, $values);

		// Used in case that there isn't a specific post URI tied to the form at compile time
		// TODO: (check that URI main is what we want)
		$values['uri:://action'] = $this->action ? : \Arr::get($values, 'uri:://action', \Uri::main());

		if ( ! empty($this->errors))
		{
			foreach ($this->errors as $k => $v)
			{
				\Arr::set($values['error'], $k.'.message', $v->get_message());
				\Arr::set($values['wrapper_class'], $k, ' error');
			}
		}

		if (\Input::method() === 'POST' and \Input::post('api_action') === $this->_id)
		{
			$values = $override_post === false ? \Arr::merge($values, \Input::post()) : \Arr::merge(\Input::post(), $values);
		}
		else
		{
			$values = \Arr::merge($this->defaults, $values);
		}
		
		// To keep the idea of complete logic separation from views(templates)
		// we need to modify the input on the selects
		foreach (array('selects', 'checks') as $sel)
		{
			if ( ! empty($this->$sel))
			{
				$prefix = $sel === 'selects' ? '__selected.' : '__checked.';

				// Run through each select/check and see whether it's posted... 
				// if it is, get the value and set...
				foreach ($this->$sel as $key)
				{
					if ($item = \Arr::get($values, $key))
					{
						if (is_array($item))
						{
							// For multi-selects?
							foreach ($item as $i)
							{
								\Arr::set($values, $prefix.$key.'.'.$i, true);
							}
						}
						else
						{
							\Arr::set($values, $prefix.$key.'.'.$item, true);
						}
					}
				}
			}
		}

		if ($this->html)
		{
			ob_start();
			include($this->html);
			$this->html = ob_get_clean();
		}
		else
		{
			$this->html = "Error retrieving form $this->name from the $this->group group";
		}
		
		// Create a new \Formloader_Mustache, otherwise it breaks the regular mustache...
		$this->rendered = (string) \Formloader_Mustache::parser($this->_id)->render($this->html, $values);
		
		return $this->rendered;
	}

	/**
	 * Sets an item in the Formloader with method chaining
	 * @param variable to set
	 * @param value to set
	 * @return $this for method chaining
	 */
	public function set($name, $val)
	{
		$this->$name = $val;
		return $this;
	}
	
	/**
	 * Checks if a file exists, as well as whether the file has an array
	 * return statement... if it's neither then it returns a blank array
	 * @param string  path of the file we're loading
	 */
	public static function load_array($file_path)
	{
		if (file_exists($file_path))
		{
			$return = \Fuel::load($file_path);

			if (is_array($return))
			{
				return $return;
			}
		}
		return array();
	}

	/**
	 * Allows us to get the pre-rendered form output by the group and name...
	 * @param group  - name of the group in the output_dir
	 * @param name   - name of the form
	 * @return array  - html path & other settings
	 */
	protected static function fetch_files($group, $name)
	{
		$output_path = \Config::get('formloader.output_path').'output/'.$group.DS;
		$view_path  = $output_path . $name . '.mustache';
		$attr_path  = $output_path . $name . '_attr.php';
		
		return array(
			'html' => (file_exists($view_path) ? $view_path : ''),
		) + self::load_array($attr_path);
	}

}