<?php

include('_config_core.php');

$block = Array();
for ($i = 97; $i <= 122; ++$i)
	$block[chr($i)] = 'This is ' . chr($i) . ' (' . $i . ')';

$oK->set('/Template/variable' , $block);
$oK->call('/Template/display', '003_included_blocks_core.html', !$cache ? microtime(true) : null);

print 'Real time: ' . number_format(microtime(true) - $START_READY, 6) . 's, ' .
		'Total time: ' . number_format(microtime(true) - $START_GLOBAL, 6) . 's';
