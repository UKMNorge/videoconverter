<?php
require_once('../inc/config.inc.php');


define('CONVERT_PASS', 'first');

$preset = 'ultrafast';
$preset_mobile = 'ultrafast';
$dbfield = 'status_first_convert';

// Already running?
$test = "SELECT `id` FROM `ukmtv`
		 WHERE `status_progress` = 'converting'
		 AND `status_first_convert` = 'converting'";
$test = mysql_query( $test );
if( mysql_num_rows( $test ) > 0 )
	die('Already converting one first-convert job. Awaiting that one');


$sql = "SELECT * FROM `ukmtv`
		WHERE `status_progress` = 'registered'
		ORDER BY `id` ASC
		LIMIT 1";
$res = mysql_query( $sql );
$cron = mysql_fetch_assoc( $res );

require_once('../inc/convert.inc.php');