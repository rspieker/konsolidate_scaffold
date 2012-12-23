<?php

include('_config_core.php');

$menu    = Array(
	'#one' => 'One',
	'#two' => 'Two',
	'#three' => 'Three',
	'#four' => 'Four',
	'#five' => 'Five'
);
$article = Array(
	'Bacon ipsum dolor sit amet.' => 'Short ribs meatloaf venison, ball tip strip steak drumstick turducken tri-tip. Chicken salami ground round, meatloaf bresaola bacon boudin drumstick brisket pork belly spare ribs filet mignon. Turducken filet mignon swine leberkas tenderloin chicken tail fatback hamburger meatloaf sirloin pancetta. Jerky tail rump spare ribs meatball venison pig shoulder. Flank t-bone bacon ball tip pork chop filet mignon swine shoulder biltong corned beef chicken strip steak. Pig beef chuck turkey shoulder, ribeye t-bone ham hock spare ribs prosciutto.',
	'Bacon tail venison doner.' => 'Corned beef ham hock jowl sausage beef hamburger pig ham. Pig tongue short ribs t-bone ham ham hock brisket salami pork belly kielbasa meatball strip steak jerky pancetta. Ham hock shank chicken ham swine. Meatloaf ground round pork loin turducken.',
	'Filet mignon drumstick pork loin.' => 'kielbasa fatback pork belly. Beef ribs hamburger ribeye shoulder biltong pancetta flank capicola pig pastrami jowl bacon short loin. Biltong flank t-bone hamburger strip steak chicken beef sausage short ribs jerky ribeye pork belly spare ribs tail. Andouille tri-tip ball tip hamburger.',
	'Leberkas shoulder spare ribs cow tongue.' => 'Sirloin short loin frankfurter, turducken leberkas short ribs pancetta pork salami tail chuck bresaola andouille kielbasa strip steak. Ham jerky drumstick ball tip pork salami, sirloin turkey. Brisket drumstick meatloaf, sausage sirloin corned beef andouille.',
	'Doner frankfurter tenderloin drumstick andouille.' => 'Bacon pig short loin shankle frankfurter andouille pork belly. Ribeye biltong tri-tip strip steak meatloaf tenderloin. Corned beef ham tail prosciutto. Turkey strip steak jerky doner flank beef ribs ground round, tri-tip short loin shoulder filet mignon sausage ham fatback tenderloin. Venison hamburger jowl leberkas, pig doner filet mignon short loin salami pastrami shankle ham tri-tip ribeye. Pig frankfurter beef ribs, bacon cow short loin tri-tip spare ribs flank chuck ball tip jowl swine.'
);

$style = Array(
	'/path/to/stylesheet1.css',
	'/path/to/stylesheet2.css',
	'/path/to/stylesheet3.css',
	'/path/to/stylesheet4.css'
);

$script = Array(
	'/path/to/javascript1.js',
	'/path/to/javascript2.js',
	'/path/to/javascript3.js',
	'/path/to/javascript4.js',
	'/path/to/javascript5.js',
);

$oK->set('/Template/menu', $menu);
$oK->set('/Template/article', $article);
$oK->set('/Template/style' , $style);
$oK->set('/Template/script' , $script);

$cache = false;
$oK->call('/Template/display', '004_realworld_core.html', !$cache ? microtime(true) . rand(1, 9999) : null);

print 'Real time: ' . number_format(microtime(true) - $START_READY, 6) . 's, ' .
		'Total time: ' . number_format(microtime(true) - $START_GLOBAL, 6) . 's';
