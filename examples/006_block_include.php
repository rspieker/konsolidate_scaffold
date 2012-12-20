<?php

//  include, configure and construct the main Konsolidate object ($oK)
include('_config.php');


//  create the template instance, loading the template (006_block_include.html)
$template = $oK->instance('/Template', '006_block_include.html');

//  assign the title placeholder
$template->title = 'Include';

//  blocks will be repeat as many times as we create them, and will have a completely isolated scope
for ($i = 0; $i < 4; ++$i)
{
	$block = $template->block('greeting');

//  assign the hello placeholder, which actually only exists in the block in the included template
	if ($i % 2 !== 0)
		$block->hello = 'Hello included block iteration ' . $i;
}

//  display the rendered template
print $template->render();