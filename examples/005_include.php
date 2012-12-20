<?php

//  include, configure and construct the main Konsolidate object ($oK)
include('_config.php');


//  create the template instance, loading the template (005_include.html)
$template = $oK->instance('/Template', '005_include.html');

//  assign the title placeholder
$template->title = 'Include';

//  assign the hello placeholder, which actually only exists in the included template
$template->hello = 'Hello includes!';

//  display the rendered template
print $template->render();