<!DOCTYPE html>
<head>
	<meta charset="utf-8">
	<title>Formbuilder Module</title>
	
	<!--[if lt IE 9]>
	 <script src="//html5shim.googlecode.com/svn/trunk/html5.js"></script>
	<![endif]-->

	<?= Asset::css(array(
		'bootstrap.css',
		'jquery-ui-1.8.16.custom.css',
		'jquery.tagit.css',
		'formloader.css',
	)); ?>
	
	<?php if (Fuel::$env === 'production') { 
	echo Asset::js('https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js');
	} else { 
	echo Asset::js('jquery-1.7.1.js');
	} ?>

	<?php if (Input::get('ref') === 'popup') { ?>
		<style type="text/css" media="screen">
		body {
		  padding-top: 0px; /* 40px to make the container go all the way to the bottom of the topbar */
		}			
		</style>
	<?php } ?>
</head>