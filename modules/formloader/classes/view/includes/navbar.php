<?php

namespace Formloader;

class View_Includes_Navbar extends \ViewModel
{
	public function view()
	{
		$navbar = '';
		foreach (array('forms', 'fieldsets', 'fields', 'actions') as $group)
		{
			$class = 'dropdown' . (\Uri::segment(2) === $group ? ' active' : '');
			$navbar .= html_tag('li', array('class' => $class), \View::forge('includes/type', array('type' => $group))) . PHP_EOL;
		}
		\View::set_global('navbar', $navbar, false);
	}
}