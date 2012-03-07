<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title>Formloader Error</title>
	<style type="text/css">
		* { margin: 0; padding: 0; }
		body { background-color: #EEE; font-family: sans-serif; font-size: 16px; line-height: 20px; margin: 40px; }
		#wrapper { padding: 30px; background: #fff; color: #333; margin: 0 auto; width: 600px; }
		a { color: #36428D; }
		h1 { color: #000; font-size: 55px; padding: 0 0 25px; line-height: 1em; }
		.intro { font-size: 22px; line-height: 30px; font-family: georgia, serif; color: #555; padding: 29px 0 20px; border-top: 1px solid #CCC; }
		h2 { margin: 50px 0 15px; padding: 0 0 10px; font-size: 18px; border-bottom: 1px dashed #ccc; }
		h2.first { margin: 10px 0 15px; }
		p { margin: 0 0 15px; line-height: 22px;}
		a { color: #666; }
		pre { border-left: 1px solid #ddd; line-height:20px; margin:20px; padding-left:1em; font-size: 14px; }
		pre, code { color:#137F80; font-family: Courier, monospace; }
		pre {
		 white-space: pre-wrap;       /* css-3 */
		 white-space: -moz-pre-wrap;  /* Mozilla, since 1999 */
		 white-space: -pre-wrap;      /* Opera 4-6 */
		 white-space: -o-pre-wrap;    /* Opera 7 */
		 word-wrap: break-word;       /* Internet Explorer 5.5+ */
		}
		ul { margin: 15px 30px; }
		li { line-height: 24px;}
		.footer { color: #777; font-size: 12px; margin: 40px 0 0 0; }
	</style>
</head>
<body>
	<div id="wrapper">
		<h1>Formloader Error</h1>

		<p class="intro"></p>

		<p>
			The Formloader module requires the assets to be copied from <br>
			<pre><code><?= $asset_source ?></code></pre> into <br>
			<pre><code><?= $asset_destination ?></code></pre>
			(the application's public assets folder).
		</p>
		<br>
		<p>
			Please run <br>
			<pre><code>php oil r formloader</code></pre><br> 
			or copy them manually
		</p>

		<p class="footer">
			<a href="http://formloader.tgriesser.com">Formloader</a> and <a href="http://fuelphp.com">FuelPHP</a> are released under the MIT license.
		</p>
	</div>
</body>
</html>
