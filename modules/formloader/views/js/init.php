
	Formloader.Views.<?=$var_name?> = new Formloader.View.AutoComplete({
		  el : $("<?= $jquery_elem ?>")
		, collection : new Formloader.Collection([], {type : "<?= $collection ?>"})
		<?= $sub_call ?>
	});
	Formloader.Views.<?= $var_name ?>.collection.reset(<?= $return ?>);
	Formloader.Views.<?= $var_name ?>.addStartTags(<?= $init ?>);
