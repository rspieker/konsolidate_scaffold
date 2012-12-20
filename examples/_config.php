<?php

//  we like to define our paths (change it to your path)
define('CLASS_PATH', realpath('path/to/your/classes'));
define('KONSOLIDATE_PATH', realpath(CLASS_PATH . '/konsolidate'));

//  include the konsolidate class (if you don't yet have it, download it from https://github.com/konfirm/konsolidate)
include(KONSOLIDATE_PATH . '/konsolidate.class.php');

//  set up the Konsolidate tiers
$tier = Array(
	'Scaffold' => realpath(CLASS_PATH . '/scaffold'),
	'Core'     => realpath(KONSOLIDATE_PATH . '/core')
);

//  and there it is.. Konsolidate now has the Scaffolding features (assuming you fixed the paths to match your development environent
$oK = new Konsolidate($tier);

//  turn of the default template filtering, so we can actually read the HTML output
$oK->set('/Config/Template/filters', '');
