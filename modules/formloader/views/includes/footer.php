		<hr>
		<footer class="pull-center">
			<p>&copy; tgriesser <?= date('Y') ?></p>
		</footer>
		
		<?= Asset::js(array(
			'https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.16/jquery-ui.min.js',
			'underscore.js',
			'backbone.js',
			'bootstrap.js',
			'tag-it.js',
			'formloader.js'
		))?>

<!-- Generates the appropriate JS defaults -->
<?php if (Uri::segment(3) and Uri::segment(3) !== 'list') { ?>
<script type="text/javascript" charset="utf-8" src="<?= Uri::create('formloader/js/'. Uri::segment(2)) ?>?item=<?= Uri::segment(3, '')?>"></script>
<?php if (isset($unhide)) { ?>
<script>
	Formloader.unhide = <?=$unhide?>;
</script>
<?php } } ?>