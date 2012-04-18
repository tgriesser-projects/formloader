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
	 * Action, sets the URL for where the form will
	 * @var string
	 */
	public $action;
	
	/**
	 * Static method or HMVC call for when a form is successfully submitted
	 * @var string
	 */
	public $route_success;
	
	/**
	 * Static method or HMVC call for when a form is unsuccessfully submitted
	 * @var string
	 */
	public $route_error;
	
	/**
	 * Whether to show the alerts for a form at the top of a form
	 * @var bool
	 */
	public $alert = true;
	
	/**
	 * When set to true, the return value for the static method
	 * or HMVC request is output in place of the form
	 * @var bool
	 */
	public $render_calls = true;

	/**
	 * Determine whether to use CSRF on a particular form
	 * by default, this is determined by the formloader config
	 * @var bool
	 */
	public $use_csrf;

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
		$return = '';
		if ($this->alert)
		{
			$return .= $this->get_alert() . PHP_EOL;
		}
		return $return . $this->render();
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
				\Arr::set($this->values, $key, $val);
			}
		}
		else
		{
			\Arr::set($this->values, $item, $value);
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
		if (\Input::method() === 'POST' and \Input::post('api_action') === $this->_id)
		{
			if ($this->validate() !== false and $this->route_success)
			{
				$use_csrf = is_bool($this->use_csrf) ? $this->use_csrf : \Config::get('formloader.csrf');

				if ($use_csrf and ! \Security::check_token())
			    {
			    	$this->set_alert('Invalid form submission, please try again.', 'error', $this->_id);
			    }
			    else
			    {
			    	unset($_POST[\Config::get('security.csrf_token_key')]);
			    	$this->routing($this->route_success, 'success');
			    }
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

			// Check whether there was a json response or an array from the call 
			if (is_array($response))
			{
				$this->set_alert($response, null, $this->_id);
			}
			else
			{
				// Json Decoded response
				$json_decoded = json_decode($response);

				if (is_array($json_decoded))
				{
					$this->set_alert($json_decoded, null, $this->_id);
				}
				elseif ( ! empty($response) and $this->render_calls)
				{
					$this->rendered = $response;
				}
			}
		}
		catch (\HttpNotFoundException $e)
		{
			$this->set_alert('404 Form Target Not found...', 'error');
		}
		catch (FormloaderException $e)
		{
			$this->set_alert($e->getMessage(), 'error');
		}
	}

	/**
	 * Sets the alert on the current form object
	 * @param string  - message for the alert
	 * @param string  - type {success|error|warning|info}
	 * @return $this - for method chaining
	 */
	public function set_alert($message, $type = 'success')
	{
		static::alert_set($message, $type, $this->_id);
		return $this;
	}

	/**
	 * Sets the alerts for this block
	 * @param string  - message for the alert
	 * @param string  - type {success|error|warning|info}
	 * @param string  - name of the form (for calling individual form alerts)
	 */
	public static function alert_set($message, $type = 'success', $id = 'default')
	{
		if (is_array($message))
		{
			$type = $message['type'] ? : 'success';
			$message = $message['message'] ? : 'Unknown Message';
		}

		$flashes = \Session::get_flash('formloader_alert', array());
		$flashes[$id][] = array(
			'message' => $message,
			'type' => $type
		);
		\Session::set_flash('formloader_alert', $flashes);
	}

	/**
	 * Renders the alert block for all of the alerts queued for
	 * the current request...
	 * @return string
	 */
	public function get_alert()
	{
		return static::alert_get($this->_id);
	}

	/**
	 * Renders the alerts based on their ID
	 */
	public static function alert_get($id = 'default')
	{
		$html = '';
		$flash = \Session::get_flash('formloader_alert');

		$alert_stack = function($id) use(&$html, $flash)
		{
			if ( ! empty($flash) and isset($flash[$id]) and ! empty($flash[$id]))
	   		{
	   			foreach ($flash[$id] as $alert)
	   			{
	   				$html .= \View::forge('flash', $alert) . PHP_EOL;
	   			}
	   		}
		};

		if (is_array($id))
		{
			foreach ($id as $type)
			{
				$alert_stack($type);
			}
		}
		else
		{
			$alert_stack($id);
		}

   		return $html;
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
		
		$use_csrf = is_bool($this->use_csrf) ? $this->use_csrf : \Config::get('formloader.csrf');

		if ($use_csrf)
		{
			$this->hidden(\Config::get('security.csrf_token_key'), \Security::fetch_token());
		}

		$values = \Arr::merge($this->values, $values);

		// Used in case that there isn't a specific post URI tied to the form at compile time
		// TODO: (check that URI main is what we want)
		$values['uri:://action'] = ! is_null($this->action) ? $this->action : \Arr::get($values, 'uri:://action', \Uri::main());

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

		// Populate the dynamic multi-items at runtime
		if ( ! empty($this->option_calls))
		{
			foreach ($this->option_calls as $field => $func)
			{
				\Arr::set($values, $field, is_callable($func) ? call_user_func($func) : array());
			}
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