<!DOCTYPE html>
<html lang="en">
<?= View::forge('includes/header') ?>
  <body>
		<div class="container">
			<div class="content">
				<div class="page-header">
					<h3><?= $title ?></h3>
				</div>
				<?= $content ?>
			</div>
		</div>
	</body>
</html>