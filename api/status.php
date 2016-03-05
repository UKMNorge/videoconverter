<?php

require_once('UKMconfig.inc.php');
require_once('../inc/config.inc.php');

function queue_count( $sql ) {
	$res = mysql_query( $sql );
	$cron = mysql_fetch_assoc( $res );
	return $cron['count'];
}

// UKMvideo vil curle denne adressen ved hver visning for å se status på videoconverter
// Introdusert 2013

$info = new stdClass;
$info->queue = new stdClass;
$info->diskspace = diskfreespace("/");

// ANTALL FIRST-CONVERT I KØ
$sql = "SELECT COUNT(`id`) AS `count`
		FROM `ukmtv`
		WHERE `status_progress` = 'registered'";
$info->queue->first_convert = queue_count( $sql );

// ANTALL FINAL-CONVERT I KØ
$sql = "SELECT COUNT(`id`) AS `count`
		FROM `ukmtv`
		WHERE `status_progress` = 'converting'
		AND `status_first_convert` = 'complete'";
$info->queue->final_convert = queue_count( $sql );

// ANTALL ARCHIVE-CONVERT I KØ
$sql = "SELECT COUNT(`id`) AS `count`
		FROM `ukmtv`
		WHERE `status_progress` = 'archive'
		AND `status_final_convert` = 'complete'";
$info->queue->archive_convert = queue_count( $sql );

die(json_encode($info));