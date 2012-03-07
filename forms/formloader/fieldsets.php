<?php
/**
 * Formloader: Updated by Formloader Module - 2012/02/28 22:14:26
 * --- You may edit the below, changes will not be lost, just appended---
 */
return array(
		'field_settings' => array(
				'legend' => 'Field Settings',
				'view' => 'dropdown.mustache',
								'fields' => array('field_label','help_inline','tip','default','input_template','field_html'),
		),
		'fieldset_settings' => array(
				'legend' => 'Fieldset Settings',
				'view' => 'dropdown.mustache',
								'fields' => array('legend','use_legend'),
		),
		'form_routing' => array(
				'legend' => 'Submission Routing',
								'fields' => array('route_success'),
		),
		'form_settings' => array(
				'legend' => 'Form Settings',
				'use_legend' => 'true',
				'view' => 'dropdown.mustache',
								'fields' => array('title','route_success','route_error'),
		),
		'options' => array(
				'attributes' => array(
						'data' => array(
								'formloader-optsettings' => 'true',
						),
						'style' => 'display:none',
				),
								'fields' => array('options','option_static_call'),
		),
		'view_info' => array(
				'legend' => 'View Path Info',
				'view' => 'dropdown.mustache',
								'fields' => array('view','view_directory','view_path','view_html'),
		),
);
