<a href="#" class="dropdown-toggle" data-toggle="dropdown">
	<?= ucfirst($type) ?>
	<b class="caret"></b>
</a>
<ul class="dropdown-menu">
	<li><a href="<?=Uri::create('formloader/'.$type.'/list')?>">List</a></li>
	<li><a href="<?=Uri::create('formloader/'.$type.'/create')?>">Create New</a></li>
</ul>