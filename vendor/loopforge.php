<?php

namespace Loopforge;

/**
 * Part of the Loopforge package for Fuel
 *
 * @package   Loopforge
 * @version   1.0
 * @author    Tim Griesser
 * @license   MIT License
 * @copyright 2012 Tim Griesser
 * @link      http://tgriesser.com
 */
abstract class Loopforge
{
	/**
	 * The Loopforge class is helpful in creating full objects from otherwise incomplete arrays
	 * 
	 * Runs through the associative array... the values which are closure objects are each called individually,
	 * in succession, with each function passed a reference of the overall array. This overall array is updated with each closure call...
	 * 
	 * This is useful for several reasons - it can store exceptions as they are needed in the application if a variable is not set,
	 * it can provide default values, or process sub-items based on other array values, without needing to store every value in the database.
	 * With this, we (shouldn't) ever need to use an isset($var['item']) in the application logic
	 * 
	 * Any item that has __remove__ after evaluation will be removed...
	 * 
	 * @param *array(s)...
	 * @return array
	 */
	public static function process_arrays()
	{
		// Get the called class so we're using the right defaults from the _init if this is called from an extended class
		$c = get_called_class();

		// Allows us to pass in multiple arguments to override the preceding arguments
		$args = func_get_args();

		// Ensure that all of the items passed in are indeed arrays, or if they're objects - convert them over
		foreach ($args as &$arg)
		{
			if ( ! is_array($arg))
			{
				if (is_object($arg))
				{
					$arg = get_object_vars($arg);
				}
				else
				{
					throw new \Exception("All arguments passed into process_arrays must be traversable");
				}
			}
		}

		// Makes the $args the full with the class's static::$_defaults being first
		if (isset($c::$_defaults))
		{
			array_unshift($args, $c::$_defaults);
		}
		
		$array = call_user_func_array("\Arr::merge", $args);

		// Loop through the array and parse closures and call_user_func's...
		array_walk_recursive($array, function(&$object, $key) use(&$array)
		{
			// Let's call the closure if that's what this is...
			if ($object instanceof \Closure)
			{
				$object = $object($array);
			}
		});
		
		$array = self::array_filter_recursive($array, function($item)
		{
			return ($item !== '__remove__');
		});

		return $array;
	}
	
	/**
	 * Applies the default array_filter, recursively
	 * @param array
	 * @param callback (optional)
	 */
	public static function array_filter_recursive($array, $func = '')
	{
		foreach ($array as $k => &$value)
		{
			if (is_array($value))
			{
				$value = self::array_filter_recursive($value, $func);
			}
		}
		if ($func instanceof \Closure)
		{
			return array_filter($array, $func);
		}
		else
		{
			return array_filter($array);
		}
	}
	
	/**
	 * Removes the prefixed keys from the array
	 * @param Array   the array we're filtering on...
	 * @param String  what prefix we're looking to remove
	 */
	public static function remove_prefixed($arr, $prefix)
	{
		$prefixed = array_keys(\Arr::filter_prefixed($arr, $prefix, false));
		return \Arr::filter_keys($arr, $prefixed, true);
	}
}