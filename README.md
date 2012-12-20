#Konsolidate scaffold

Scaffolding tier for Konsolidate (https://github.com/konfirm/konsolidate)



##Basic usage


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

##Manipulating templates during execution
The scaffold template engine has a simple yet powerful phase mechanism, which allows a developer to hook into the different phases of templating.
The phases offered by the template engine are:

- initialisation (```ScaffoldTemplate::PHASE_INIT```), Triggered when starting to load the template (```load``` method)
- preparation (```ScaffoldTemplate::PHASE_PREPARE```), Triggered when the load was successful and the features are started to be processed
- ready (```ScaffoldTemplate::PHASE_READY```), Triggered when the template is fully prepared.
	
	This is the phase in which you will receive the template object if an instance is created with a template source. Otherwise this phase is reached after the ```load``` method is ready)
- replace (```ScaffoldTemplate::PHASE_REPLACE```), Triggered when the template starts to replace the placeholders
- pre-render (```ScaffoldTemplate::PHASE_PRE_RENDER```), Triggered after the replacement phase when processing the template features
- render (```ScaffoldTemplate::PHASE_RENDER```), Triggered when the internal rendering is fully done, right before the final rendering is returned
- assign (```ScaffoldTemplate::PHASE_ASSIGN```), Triggered when assigning properties to the template. This behaves more like an event than a true phase.

###Assigning phase hooks
The phase hooks require a valid PHP callback (either a string containing a functionname or an array containing an object reference and its method name), for example:

```php

<?php
//  …template creation goed here… 

//  create the 'comment' function which will do some manipulation
function comment($hook)
{
	//  the $hook variable is a stdClass object with the following properties:
	//  - type: (string) the name of the phase
	//  - dom:  (DOMDocument object) the actual DOMDocument, at its current state of manipulation
	//  - xpath: (DOMXPath object) a ready to use DOMXPath object, loaded with the actual DOMDocument
	//  - template: (ScaffoldTemplate object) the template object itself

	//  For the assignment phase (ScaffoldTemplate::PHASE_ASSIGN) there's two more properties:
	//  - property: (string) assigned property name (by reference)
	//  - value: (mixed) the assigned property value (by reference)

	//  Now, let's manipulate the DOM a bit by appending a comment node to the <body>
	foreach ($hook->xpath->query('//body') as $bodyNode)
		$bodyNode->appendChild(
			$hook->dom->createComment('I successfully manipulated the template')
		);
}

//  assigning the phase hook for the 'render' phase
$template->addHook(ScaffoldTemplate::PHASE_RENDER, 'comment');

```