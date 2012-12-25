<?php

set_time_limit(120);
ini_set('memory_limit', '128M');

$files  = glob('00*php');
$result = Array();

foreach ($files as $file)
{
	$part  = explode('_', basename($file, '.php'));
	$void  = array_shift($part); //  strip the number
	$name  = array_pop($part);   //  obtain the tier
	$title = implode(' ', $part);

	if (!isset($result[$title]))
		$result[$title] = Array();

	//  start the test
	$real = Array();
	for ($test = 0; $test < 50; ++$test)
	{
		$cache = false;
		ob_start();
		include($file);
		$output = ob_get_clean();
		if (preg_match('/Real time: ([0-9\.]+)s/', $output, $match))
			$real[] = (double) $match[1];
	}
	sort($real);

	$result[$title][$name] = Array(
		'avg' => array_sum($real) / count($real),
		'min' => array_shift($real),
		'max' => array_pop($real)
	);
}

foreach ($result as $name=>$tier)
{
	//  ironically, this is done without any templates ;-)
	?>
		<h2><?= $name ?></h2>
		<table>
			<thead>
				<tr>
					<th>tier</th>
					<th>min</th>
					<th>avg</th>
					<th>max</th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td>Core</td>
					<td><?= $tier['core']['min'] ?></td>
					<td><?= $tier['core']['avg'] ?></td>
					<td><?= $tier['core']['max'] ?></td>
				</tr><tr>
					<td>Scaffold</td>
					<td><?= $tier['scaffold']['min'] ?></td>
					<td><?= $tier['scaffold']['avg'] ?></td>
					<td><?= $tier['scaffold']['max'] ?></td>
				</tr><tr>
					<td><!-- --></td>
					<td><?= number_format(($tier['scaffold']['min']) / $tier['core']['min'], 1) ?></td>
					<td><?= number_format(($tier['scaffold']['avg']) / $tier['core']['avg'], 1) ?></td>
					<td><?= number_format(($tier['scaffold']['max']) / $tier['core']['max'], 1) ?></td>
				</tr>
			</tbody>
		</table>
	<?
}