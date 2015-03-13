<?php
// SCRIPT WILL FINISH IF USER LEAVES THE PAGE
ignore_user_abort(true);

require_once('UKMconfig.inc.php');
require_once('../inc/config.inc.php');


define('CONVERT_PASS', 'archive');

$preset = 'slower';
$preset_mobile = 'slower';
$dbfield = 'status_archive';

// Already running a final-pass?
$test = "SELECT `id` FROM `ukmtv`
		 WHERE (`status_progress` = 'converting' AND `status_final_convert` = 'converting')
		 OR (`status_progress` = 'converting' AND `status_archive` = 'converting')";
$test = mysql_query( $test );
if( mysql_num_rows( $test ) > 0 )
	die('Already converting one final-convert or archive job. Awaiting that one');

// If first-passes still queued, take a nap
$test = "SELECT `id` FROM `ukmtv`
		 WHERE `status_progress` = 'registered'";
$test = mysql_query( $test );
if( mysql_num_rows( $test ) > 0 )
	die('First-convert jobs to be done. Taking a nap');

// FIND NEXT JOB
$sql = "SELECT * FROM `ukmtv`
		WHERE `status_progress` = 'archive'
		AND `status_final_convert` = 'complete'
		AND (`status_archive` IS NULL OR `status_archive` = 'convert')
		ORDER BY `id` ASC
		LIMIT 1";
$res = mysql_query( $sql );
$cron = mysql_fetch_assoc( $res );

require_once('../inc/convert.inc.php');