<?php

//  include, configure and construct the main Konsolidate object ($oK)
include('_config.php');


//  create the template instance, loading the template (002_require.html)
$template = $oK->instance('/Template', '002_require.html');

//  assign the title placeholder, note that the title placeholder has two different default values but it is still a single placeholder, which means both title placeholders will end up having the same value
$template->title = 'My First Scaffold Attempt';

//  assign the hello placeholder
$template->hello = 'Hello Scaffold Template!';

//  we don't have to do anything for the requires to start working, this is all taken care of by Scaffold Template ;-)

//  display the rendered template
print $template->render();