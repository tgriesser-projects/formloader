<?php
/**
 * Formloader: Updated by Formloader Module - 2012/03/07 02:36:21
 * --- You may edit the below, changes will not be lost, just appended---
 */
return array(
		'actions' => array(
				'attributes' => array(
						'class' => 'form-horizontal',
				),
				'title' => 'Create',
				'fieldsets' => array('template_info'),
				'fields' => array('group','action_name','attributes_action'),
				'actions' => array('save','preview'),
		),
		'fields' => array(
				'attributes' => array(
						'class' => 'form-horizontal',
				),
				'title' => 'Create Form Field',
				'fieldsets' => array('options','field_settings','template_info'),
				'fields' => array('group','field_name','validations','subfields','attributes_field'),
				'actions' => array('save','preview'),
		),
		'fieldsets' => array(
				'attributes' => array(
						'class' => 'form-horizontal',
				),
				'title' => 'Create Fieldset',
				'fieldsets' => array('fieldset_settings','template_info'),
				'fields' => array('group','fieldset_name','fields','attributes_fieldset'),
				'actions' => array('save','preview'),
		),
		'forms' => array(
				'attributes' => array(
						'class' => 'form-horizontal',
				),
				'title' => 'Create New Form',
				'fieldsets' => array('form_settings','template_info'),
				'fields' => array('group','form_name','fieldsets','fields','actions','attributes_form'),
				'actions' => array('compile','preview'),
		),
		'ignored_groups' => array(
				'attributes' => array(
						'class' => 'form-horizontal',
				),
				'route_success' => 'Formloader_Scaffold::ignored_groups',
				'fields' => array('ignored_groups'),
				'actions' => array('set'),
		),
);
