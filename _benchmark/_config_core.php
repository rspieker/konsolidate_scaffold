<?php

$START_GLOBAL = microtime(true);

//  we like to define our paths, we need to check if these are set, as the benchmark index.php will keep including this
if (!defined('CLASS_PATH'))
	define('CLASS_PATH', realpath('path/to/your/classes'));
if (!defined('KONSOLIDATE_PATH'))
	define('KONSOLIDATE_PATH', realpath(CLASS_PATH . '/konsolidate'));

//  include the konsolidate class
include_once(KONSOLIDATE_PATH . '/konsolidate.class.php');

//  set up the Konsolidate tiers
$tier = Array(
	'Core'     => realpath(KONSOLIDATE_PATH . '/core')
);

//  and there it is.. Konsolidate now has the Scaffolding features (assuming you fixed the paths to match your development environent
$oK = new Konsolidate($tier);

//  Set up the template and compilation paths
$oK->set('/Config/Path/template', './template');
$oK->set('/Config/Path/compile', sys_get_temp_dir());

$START_READY = microtime(true);
