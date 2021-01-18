<?php
require_once('UKMconfig.inc.php');
require_once('../inc/headers.inc.php');
require_once('../inc/config.inc.php');

function queue_count( $sql ) {
	$res = $db->query( $sql );
	$cron = $res->fetch_assoc();
	return $cron['count'];
}

// UKMvideo vil curle denne adressen ved hver visning for å se status på videoconverter
// Introdusert 2013

$info = new stdClass;
$info->queue = new stdClass;
$info->time = new stdClass;
$info->diskspace = diskfreespace("/");
$info->total_diskspace = disk_total_space("/");

// ANTALL FIRST-CONVERT I KØ
$sql = "SELECT COUNT(`id`) AS `count`
		FROM `ukmtv`
		WHERE `status_progress` = 'registered'";
$info->queue->first_convert = queue_count( $sql );

// ANTALL FINAL-CONVERT I KØ
$sql = "SELECT COUNT(`id`) AS `count`
		FROM `ukmtv`
		WHERE `status_progress` = 'converting'
		AND `status_first_convert` = 'complete'
		AND `status_final_convert` IS NULL";
$info->queue->final_convert = queue_count( $sql );

// ANTALL ARCHIVE-CONVERT I KØ
$sql = "SELECT COUNT(`id`) AS `count`
		FROM `ukmtv`
		WHERE `status_progress` = 'archive'
		AND (`status_archive` IS NULL OR `status_archive` = 'convert')";
$info->queue->archive_convert = queue_count( $sql );


// KORRIGER FOR AKKUMULERING AV ARBEIDSOPPGAVER
$info->queue->archive_convert = $info->queue->archive_convert + $info->queue->final_convert + $info->queue->first_convert;
$info->queue->final_convert = $info->queue->final_convert + $info->queue->first_convert;


// BEREGN TID
$info->time->first_convert = $info->queue->first_convert * 5; // Estimated 5 min
$info->time->final_convert = $info->queue->final_convert * 10; // Estimated 10 min
$info->time->archive_convert = $info->queue->archive_convert * 25; // Estimated 25 min

$info->time->total = (int)$info->time->archive_convert + (int)$info->time->final_convert + (int)$info->time->first_convert;

die(json_encode($info));
