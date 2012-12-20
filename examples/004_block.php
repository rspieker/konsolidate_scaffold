<?php

//  include, configure and construct the main Konsolidate object ($oK)
include('_config.php');


//  create the template instance, loading the template (003_block.html)
$template = $oK->instance('/Template', '003_block.html');

//  assign the title placeholder
$template->title = 'Blocks';

//  assign the hello placeholder, which actually does not exist in the template, as it has moved into a <k:block> node
$template->hello = 'Hello blocks!';


//  blocks will be repeat as many times as we create them, and will have a completely isolated scope
for ($i = 0; $i < 4; ++$i)
{
	$block = $template->block('greeting');

	//  we will only assign a value to the 'hello' placeholder if $i is an odd number, this means that for the blocks which don't have an assigned value, the default value (if any) will be used
	if ($i % 2 !== 0)
		$block->hello = 'Hello iteration ' . $i;
}


//  display the rendered template
print $template->render();