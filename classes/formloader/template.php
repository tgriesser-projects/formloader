<?php

namespace Formloader;

/**
 * Part of the Formloader package for Fuel
 *
 * Adds an additional view path for templates in object creation
 * 
 * @package   Formloader
 * @version   1.0
 * @author    Tim Griesser <tim@tgriesser.com>
 * @license   MIT License
 * @copyright 2012 Tim Griesser
 * @link      http://formloader.tgriesser.com
 */
class Formloader_Template extends \View
{
	/**
	 * Sets the view filename.
	 *
	 *     $view->set_filename($file);
	 *
	 * @param   string  view filename
	 * @return  View
	 * @throws  FuelException
	 */
	public function set_filename($file)
	{
		// set find_file's one-time-only search paths
		\Finder::instance()->flash($this->request_paths);

		// locate the view file, in the templates folder...
		if (($path = \Finder::search('templates', $file, '.'.$this->extension, false, false)) === false)
		{
			throw new \FuelException('The requested view could not be found: '.\Fuel::clean_path($file));
		}

		// Store the file path locally
		$this->file_name = $path;

		return $this;
	}
}