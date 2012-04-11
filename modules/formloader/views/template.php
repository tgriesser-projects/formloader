<!DOCTYPE html>
<html lang="en">
<?= $header ?>
<body>
	<div class="container">
		<div class="content">
			<?php if ($github) { ?>
			<a href="http://github.com/tgriesser/formloader">
				<img style="position: absolute; top: 0; right: 0; border: 0;" src="https://a248.e.akamai.net/assets.github.com/img/71eeaab9d563c2b3c590319b398dd35683265e85/687474703a2f2f73332e616d617a6f6e6177732e636f6d2f6769746875622f726962626f6e732f666f726b6d655f72696768745f677261795f3664366436642e706e67" alt="Fork me on GitHub">
			</a>
			<?php } ?>
			<h1>Formloader Module</h1>
			<hr>
			<?= $navbar ?>
			<?= Formloader::alert_get() ?>
			<br>
			<?php if ( ! empty($title)) { ?>
				<h3><?= $title ?></h3>
				<hr>
			<?php } ?>
			<?= $content ?>
		<?= $footer ?>
		</div>
	</div>
</body>
</html>