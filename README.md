#Konsolidate scaffold

Scaffolding tier for Konsolidate (https://github.com/konfirm/konsolidate)

>Please not that the XML based templating that comes with the Scaffold-tier is slower than the default template engine that ships with Konsolidate.
>This is mainly due to the use of DOM documents combined with the more powerful options in the Scaffold template engine.
>A lot of lost performance has already been addressed in a recent update to Konsolidate itself, bringing down the overhead from using blocks from 35 times slower to 'just' 5 times slower (CoreTemplate: 0.0022 seconds vs ScaffoldTemplate: 0.0111 seconds creating 26 blocks).

>The real world benchmark (which isn't all that real world, but it mixes common template functionality) is down from 4.7 times slower to 'just' 1.8 times slower. In seconds this boils down to CoreTemplate: 0.0078 seconds vs ScaffoldTemplate: 0.0147 seconds.

>I do believe that the Scaffold template uses much cleaner templates and will be able to improve the development flow, and we are actually talking about milliseconds here.

>While I will continue to optimize the performance I do consider the overhead to be acceptable, as the benefits of ScaffoldTemplate exceed those of CoreTemplate; e.g. security (implicit output escaping), automatic resolvement of external requirements (only the requirements actually used are in the output) and your front-enders don't need to learn PHP basics.
> Last but most certainly not least, the hooks that allow you to do advanced pre-/postprocessing directly on the DOM allow for way more flexibility than CoreTemplate (even though similar functionality could be achieved with CoreTemplate  using output buffering).


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
//  …template creation goes here…

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

##Using the CSP (Content Security Policy) feature
Like any other feature, you can add the CSP feature using the feature syntax ```<k:csp />```, however the CSP headers generated would block nearly all content for the page. A basic understanding of CSP is required, as misconfiguration is probably worse than none at all. A good starting point would be http://www.html5rocks.com/en/tutorials/security/content-security-policy/, as it explains the basic mechanics.
The base configuration of the CSP feature is done using attributes on the ```<k:csp />``` node, using a slightly shortened syntax.
For example: ```<k:csp default="self" />``` will allow any additional resource to originate from the same (sub-)domain as the page itself. After the initial configuration the CSP-feature will add any URL for javascripts and stylesheets added using the ```<k:require file="..." />``` feature, this will leave out any inline script/style so if you cannot move the script/style into an external file, you will need the 'unsafe-inline' option added to the default-src, script-src and/or style-src.
Current available attributes are:
- ```default``` - default-src, the default policy
- ```script``` - script-src, the script policy (extended using the javascript file required with ```<k:require file="..." />```)
- ```style``` - style-src, the stylesheet policy (extended using the javascript file required with ```<k:require file="..." />```)
- ```img``` - img-src, the image policy
- ```media``` - media-src, the media (video and audio) policy
- ```frame``` - frame-src, the frame policy
- ```font``` - font-src, the font policy
- ```connect``` - connect-src, the connect policy (where can XMLHTTPRequest, Websockets, etc connect to)
- ```report``` - report-uri, where to send the report (note that FireFox won't send the report cross-domain, so it needs to comply with the 'self' rule)

As the CSP specification have been a working draft for quite some time, there are three types of headers available, the CSP-feature looks at the user-agent to decide which one to use (Content-Security-Policy, X-Content-Security-Policy or X-Webkit-CSP) and adapts the contents of the policy to reflect the supported state.