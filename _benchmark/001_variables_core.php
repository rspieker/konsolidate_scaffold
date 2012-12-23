<?php

include('_config_core.php');

for ($i = 97; $i <= 122; ++$i)
	$oK->set('/Template/' . chr($i), 'This is ' . chr($i) . ' (' . $i . ')');

$oK->call('/Template/display', '001_variables_core.html', !$cache ? microtime(true) : null);

print 'Real time: ' . number_format(microtime(true) - $START_READY, 6) . 's, ' .
		'Total time: ' . number_format(microtime(true) - $START_GLOBAL, 6) . 's';
