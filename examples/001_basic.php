<?php

//  include, configure and construct the main Konsolidate object ($oK)
include('_config.php');


//  create the template instance, loading the template (000_basic.html)
$template = $oK->instance('/Template', '000_basic.html');

//  assign the title placeholder, note that the title placeholder has two different default values but it is still a single placeholder, which means both title placeholders will end up having the same value
$template->title = 'My First Scaffold Attempt';

//  assign the hello placeholder
$template->hello = 'Hello Scaffold Template!';

//  display the rendered template
print $template->render();