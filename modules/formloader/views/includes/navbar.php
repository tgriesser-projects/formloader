<div class="subnav">
	<ul class="nav nav-pills">
		<?= $navbar ?>
		<li class="dropdown <?=Uri::segment(2) === 'settings' ? 'active' : '' ?>">
			<a href="#" class="dropdown-toggle" data-toggle="dropdown">
				Config
				<b class="caret"></b>
			</a>
			<ul class="dropdown-menu">
				<li><a href="<?=Uri::create('formloader/settings')?>">Settings</a></li>
			</ul>
		</li>
	</ul>
</div>
<br>