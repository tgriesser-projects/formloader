<h3>Update Formloader:</h3>
<hr>
<p>Updating the formloader will delete the core items in</p>
	<ul>
		<li>
			<code>"app/modules/formloader"</code>
		</li> 
		<li>
			<code>"public/assets/formloader"</code>
		</li>
	</ul>
<p>folders and replace them with the core items from:</p>
	<ul>
		<li>
			<code>"packages/formloader/bundle"</code>
		</li> 
		<li>
			<code>"packages/formloader/modules/formloader/assets"</code>
		</li>
	</ul>

<p>Please make sure to commit or backup your current project changes, because this is not reversible.
<hr>
<form method="POST" action="<?=Uri::current()?>">
	<button type="submit" class="btn btn-warning">Update</button>
</form>
