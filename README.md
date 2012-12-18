konsolidate_scaffold
====================

Scaffolding tier for Konsolidate (https://github.com/konfirm/konsolidate)



Basic usage


The template (template/base.html):
```xml
<!DOCTYPE html>

<html>
	<head>
		<meta charset="utf-8" />
		<title>{title:this is the default title}</title>

		<k:require file="http://code.jquery.com/jquery.min.js" /><!--- require jQuery  -->
		<k:require file="http://cdn.jsdelivr.net/foundation/3.2.2/stylesheets/foundation.min.css" /><!--- require foundation stylesheet  -->

		<k:style /><!--- here will be the collected stylesheet requirements  -->
	</head>
	<body>
		<div id="capture">
			<h1>{title:Yet another default title}</h1>

			<k:block name="myBlock">
				<p>{lyrics:default lyrics}</p>
			</k:block>
		</div>

		<k:script /><!--- here will be the collected script requirements  -->
	</body>
</html>
```


The PHP code to create, populate and display the template:
```php
<?php

/**  create your Konsolidate object here, adding the new Scaffold tier, we will assume the base Konsolidate instance to reside in $oK  **/

//  create the template instance, loading the template (template/base.html)
$template = $oK->instance('/Template', 'template/base.html');

//  assign a title (otherwise the defaults are used)
$template->title = 'My First Scaffold Attempt';

//  create a block and assign some lyrics
$block = $template->block('myBlock');
$block->lyrics = 'Tada!';

//  create another block and assign some other lyrics
$block = $template->block('myBlock');
$block->lyrics = 'Neat, another block';

//  display the rendered template
print $template->render();
```