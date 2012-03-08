# Formloader Package &amp; Module

Formloader is a Package &amp; Module combination which intends to abstract all form validation &amp; generation to a single location, providing a simple UI for managing forms.

### Note:

The version number matches with the FuelPHP install it requires, however it is an early alpha release and has many features which are planned or in progress. The main public methods should generally remain the same for the 1.x branch.

---

## Introduction

When writing CRUD apps, much of the logic and views are built around handling/rendering user input. Most of this is very repetitive and scattered throughout an application. This package/module combination aims to simplify the form creation process, storing the structure for forms in a consistent fashion in the filesystem and providing a simple user interface for managing the form logic, attributes, and views.

## Installation

Clone or download this repository into your "packages" directory

Once you have downloaded the Formloader package, all you need to do is enable it in your config.

	```
	'packages' => array(
		'formloader',
	),
	```

By default, the Formloader module will only be enabled when `Fuel::$env === "development"`. You can modify this in the configuration, but do so at your own risk... you don't want a public facing interface to be able to edit forms in production mode without any security measures.

# Formloader Class
	
This class contains the methods that will be accessed in the application's controller or view... the module has whitelisted itsself by default, so the Formloader methods can be passed to/used in the view without escaping by default.

---

## Class Methods
	

###`forge($name, $group, $listen = true)`
			
The *forge* method returns a new Formloader instance, based on the name/group combination of the form. 
If `$listen` is true, then the form will process automatically if submitted.
	
<table class="method">
	<tbody>
		<tr>
			<th class="legend">Static</th>
			<td>Yes</td>
		</tr>
		<tr>
			<th>Parameters</th>
			<td>
				<tr>
					<th>Param</th>
					<th>Type</th>
					<th>Default</th>
					<th class="description">Description</th>
				</tr>
				<tr>
					<th>$name</th>
					<td><em>string</em></td>
					<td><pre><code>None</code></pre></td>
					<td>The group of the form to render</td>
				</tr>
				<tr>
					<th>$group</th>
					<td><em>string</em></td>
					<td><pre><code>None</code></pre></td>
					<td>The name of the form to render</td>
				</tr>
				<tr>
					<th>$listen</th>
					<td><em>bool</em></td>
					<td><pre><code>true</code></pre></td>
					<td>Whether the submission listener is enabled automatically</td>
				</tr>
			</td>
		</tr>
		<tr>
			<th>Returns</th>
			<td>\Formloader Object</td>
		</tr>
		<tr>
			<th>Throws</th>
			<td>FormloaderException, when the form group/name combination is invalid.</td>
		</tr>
		<tr>
			<th>Example</th>
			<td>
				<pre class="php"><code>// Render the login form
$form = \Formloader::forge('auth', 'login');
</code></pre>
			</td>
		</tr>
	</tbody>
</table>

###`validate($inputs = array(), $allow_partial = false)`
	
The *validate* method validates the form, based on any validation
settings that we have stored from the form's filesystem attributes, as well as any runtime validations added.

The arguments match \Validation::run()... any $inputs will override the POST values, and if $allow_partial is set to 'true', we will validate only the available inputs, even ignoring the required ones.

<table class="method">
	<tbody>
		<tr>
			<th class="legend">Static</th>
			<td>No</td>
		</tr>
		<tr>
			<th>Parameters</th>
			<td>
				<table class="parameters">
					<tr>
						<th>Param</th>
						<th>Type</th>
						<th>Default</th>
						<th class="description">Description</th>
					</tr>
					<tr>
						<th>$inputs</th>
						<td><em>array</em></td>
						<td><pre><code>array()</code></pre></td>
						<td>Input that appends/overwrites POST values</td>
					</tr>
					<tr>
						<th>$allow_partial</th>
						<td><em>bool</em></td>
						<td><pre><code>false</code></pre></td>
						<td>If true, this will skip validation of values it can't find or are null</td>
					</tr>
				</table>
			</td>
		</tr>
		<tr>
			<th>Returns</th>
			<td>
				bool | null<br />
				'true' on success, 'false' on failure, 'null' on no validation arguments
			</td>
		</tr>
		<tr>
			<th>Example</th>
			<td>
				<pre class="php">
					<code>// Get the Validation instance of the default Fieldset
$form = \Formloader::forge('auth', 'login', false)

if ($form->validate(array('submitted_from' => 'main_page')))
{
	echo 'The form was submitted';
}</code>
</pre>
			</td>
		</tr>
	</tbody>
</table>

###`values($item, $value = null)`

Sets any values we would like to populate the form with...

<table class="method">
	<tbody>
		<tr>
			<th class="legend">Static</th>
			<td>No</td>
		</tr>
		<tr>
			<th>Parameters</th>
			<td>
				<table class="parameters">
					<tr>
						<th>Param</th>
						<th>Type</th>
						<th>Default</th>
						<th class="description">Description</th>
					</tr>
					<tr>
						<th>$item</th>
						<td><em>string | array</em></td>
						<td><pre><code>None</code></pre></td>
						<td>Either the key of the item/value pair, or an array/object we can loop through and add to the values array</td>
					</tr>
					<tr>
						<th>$value</th>
						<td><em>string | array</em></td>
						<td><pre><code>null</code></pre></td>
						<td>If $item is a string, this will be the value of the item/value pair</td>
					</tr>
				</table>
			</td>
		</tr>
		<tr>
			<th>Returns</th>
			<td>\Formloader - current object</td>
		</tr>
		<tr>
			<th>Example</th>
			<td>
				<pre class="php"><code>// Set the values on the form
$form = \Formloader::forge('auth', 'login')
->values('email', 'foo@example.com');
</code>
</pre>
			</td>
		</tr>
	</tbody>
</table>

###`hidden($name, $val = null)`

Allows us to dynamically add hidden inputs to the form at runtime...

<table class="method">
	<tbody>
		<tr>
			<th class="legend">Static</th>
			<td>No</td>
		</tr>
		<tr>
			<th>Parameters</th>
			<td>
				<table class="parameters">
					<tr>
						<th>Param</th>
						<th>Type</th>
						<th>Default</th>
						<th class="description">Description</th>
					</tr>
					<tr>
						<th>$name</th>
						<td><em>string | array</em></td>
						<td><pre><code>None</code></pre></td>
						<td>Either the name of the hidden field array of name/value pairs to add</td>
					</tr>
					<tr>
						<th>$val</th>
						<td><em>null</em></td>
						<td><pre><code>null</code></pre></td>
						<td>If $item is a string, this will be the value of the hidden field</td>
					</tr>
				</table>
			</td>
		</tr>
		<tr>
			<th>Returns</th>
			<td>\Formloader - current object</td>
		</tr>
		<tr>
			<th>Example</th>
			<td>
				<pre class="php"><code>// Adds a hidden field
$form = \Formloader::forge('auth', 'login')
->hidden('page_uuid', 'login-12');
</code></pre>
			</td>
		</tr>
	</tbody>
</table>

###`listen()`

Listens for the form submission based on the "$group.$name" hidden field added to every form.
Initialized by default, but must be called manually if the third parameter of the <code>Formloader::forge</code> is 'false'
(usually the case when custom validations or routes are desired)

<table class="method">
	<tbody>
		<tr>
			<th class="legend">Static</th>
			<td>No</td>
		</tr>
		<tr>
			<th>Parameters</th>
			<td>
				None
			</td>
		</tr>
		<tr>
			<th>Returns</th>
			<td>Null... this is usually expected to end script execution if the call is successful</td>
		</tr>
		<tr>
			<th>Throws</th>
			<td>FormloaderException, when there isn't a callable form submission.<br/>
			\HttpNotFoundException, when the HMVC call is invalid.</td>
		</tr>
		<tr>
			<th>Example</th>
			<td>
				<pre class="php"><code>// Listen for form submission, calling route-success
$form = \Formloader::forge('auth', 'login', false)
$form->route_success = 'Controller_Auth::private_method';
$form->listen();
</code></pre>
			</td>
		</tr>
	</tbody>
</table>

###`render($values = array(), $override_post = false)`

The _render_ is called by default by the __toString method. This method can also be used if you would like to render the form explicitly, optionally allowing the inputs specified by this class to override the values of the POST array if the second argument is set to 'true'. The render will only be called once per-form, so make sure that everything is set before calling it.

<table class="method">
	<tbody>
		<tr>
			<th class="legend">Static</th>
			<td>No</td>
		</tr>
		<tr>
			<th>Parameters</th>
			<td>
				<table class="parameters">
					<tr>
						<th>Param</th>
						<th>Type</th>
						<th>Default</th>
						<th class="description">Description</th>
					</tr>
					<tr>
						<th>$values</th>
						<td><em>array</em></td>
						<td><pre><code>array()</code></pre></td>
						<td>Appended to other values for the form set with values()</td>
					</tr>
					<tr>
						<th>$override_post</th>
						<td><em>bool</em></td>
						<td><pre><code>false</code></pre></td>
						<td>If true, the items in $this->values will override those in \Input::post()</td>
					</tr>
				</table>
			</td>
		</tr>
		<tr>
			<th>Returns</th>
			<td>
				string<br />
				complete processed HTML of the form we're displaying
			</td>
		</tr>
		<tr>
			<th>Example</th>
			<td>
				<pre class="php"><code>// set some values and render the form
$form = \Formloader::forge('auth', 'login')->render();

</code></pre>
			</td>
		</tr>
	</tbody>
</table>

### load_array($file_path)

The load_array method combines checking if a file exists and checking if its inclusion returns an array, 
returning an array if one of the two isn't true.

<table class="method">
	<tbody>
		<tr>
			<th class="legend">Static</th>
			<td>Yes</td>
		</tr>
		<tr>
			<th>Parameters</th>
			<td>
				<table class="parameters">
					<tr>
						<th>Param</th>
						<th>Type</th>
						<th>Default</th>
						<th class="description">Description</th>
					</tr>
					<tr>
						<th>$file_path</th>
						<td><em>string</em></td>
						<td><pre><code>None</code></pre></td>
						<td>The path of the file we're trying to include</td>
					</tr>
				</table>
			</td>
		</tr>
		<tr>
			<th>Returns</th>
			<td>Fieldset_Field</td>
		</tr>
		<tr>
			<th>Example</th>
			<td>
				<pre class="php"><code>// Check an array
$array = \Formloader::load_array('path/to/file');

// $array = array();  unless the file returns an array... 
</code></pre>
			</td>
		</tr>
	</tbody>
</table>

---

## License:

Formloader is released under the MIT license
