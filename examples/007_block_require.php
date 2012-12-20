<?php

//  include, configure and construct the main Konsolidate object ($oK)
include('_config.php');


//  create the template instance, loading the template (007_block_require.html)
$template = $oK->instance('/Template', '007_block_require.html');

//  assign the title placeholder
$template->title = 'Blocks';

//  assign the hello placeholder, which actually does not exist in the template, as it has moved into a <k:block> node
$template->hello = 'Hello blocks!';


//  we don't do anything for the block, which means it won't be in the output
//  notice how the required stylesheet is therefor not in the rendered result

//  display the rendered template
print $template->render();