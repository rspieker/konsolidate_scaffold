<?php

//  include, configure and construct the main Konsolidate object ($oK)
include('_config.php');


//  create the template instance, loading the template (000_basic.html)
$template = $oK->instance('/Template', '000_basic.html');

//  we will not assign any placeholder variables just yet, to demonstrate the default values

//  display the rendered template
print $template->render();