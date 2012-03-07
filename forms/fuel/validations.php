<?php

return array(
	
	'required' => array(
		'clicktip' => 'This value is required (cannot be empty)'
	),
	'valid_email' => array(
		'clicktip' => 'This value must be a properly formatted email address (won\'t check if it actually exists)'
	),
	'valid_emails' => array(
		'clicktip' => 'This will be a comma separated list of emails'
	),
	'valid_url' => array(
		'clicktip' => 'The string in here must be a valid url'
	),
	'valid_ip' => array(
		'clicktip' => 'This must be a valid IP address'
	),
	'match_value' => array(
		'prompt' => 'The field input must match this:'
	),
	'match_pattern' => array(
		'prompt' => 'Will try to match the value against the given full PREG regex:'
	),
	'match_field'   => array(
		'prompt' => 'Match the field to the field with the given fieldname... <br>Important: you can only match against a field that was added before the field this rule is added to.'
	),
	'min_length' => array(
		'prompt' => 'Tests whether the string >= this many characters:'
	),
	'max_length' => array(
		'prompt' => 'Tests whether the string <= this many characters:'
	),
	'exact_length' => array(
		'prompt' => 'Tests whether the string is exactly this many characters:'
	),
	'numeric_min' => array(
		'prompt' => 'Tests whether the given input is a number that is greater than... Note: it does not check or cast the input to a numeric value'
	),
	'numeric_max' => array(
		'prompt' => 'Tests whether the given input is a number that is smaller than... Note: it does not check or cast the input to a numeric value'
	),
	'valid_string' => array(
		'prompt' => "CSV of the following... alpha, uppercase, lowercase, numeric, spaces, newlines, tabs, dots, punctuation, dashes, utf8"
	)
);