<?php
/**
 * Formloader: Updated by Formloader Module - 2012/02/28 22:14:26
 * --- You may edit the below, changes will not be lost, just appended---
 */
return array(
		'field_settings' => array(
				'legend' => 'Field Settings',
				'template' => 'dropdown.mustache',
				'fields' => array('field_label','help_inline','tip','default','input_template','field_html'),
		),
		'fieldset_settings' => array(
				'legend' => 'Fieldset Settings',
				'template' => 'dropdown.mustache',
				'fields' => array('legend','use_legend'),
		),
		'form_routing' => array(
				'legend' => 'Submission Routing',
				'fields' => array('route_success'),
		),
		'form_settings' => array(
				'legend' => 'Form Settings',
				'use_legend' => 'true',
				'template' => 'dropdown.mustache',
				'fields' => array('title','route_success','route_error'),
		),
		'options' => array(
				'attributes' => array(
						'data' => array(
								'formloader-optsettings' => 'true',
						),
						'style' => 'display:none',
				),
				'fields' => array('options','option_call'),
		),
		'template_info' => array(
				'legend' => 'Template Path Info',
				'template' => 'dropdown.mustache',
				'fields' => array('template','template_directory','template_path','template_html'),
		),
);
