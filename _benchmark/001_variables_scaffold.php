<?php

include('_config_scaffold.php');

$template = $oK->call('/Template/load', 'template/001_variables_scaffold.html');

for ($i = 97; $i <= 122; ++$i)
	$template->{chr($i)} = 'This is ' . chr($i) . ' (' . $i . ')';

print $template->render();

print 'Real time: ' . number_format(microtime(true) - $START_READY, 6) . 's, ' .
		'Total time: ' . number_format(microtime(true) - $START_GLOBAL, 6) . 's';
