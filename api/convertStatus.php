<?php
require_once('UKMconfig.inc.php');
require_once('../inc/headers.inc.php');
require_once('../inc/config.inc.php');

if( !isset( $_GET['cron_id'] ) ) {
	die( json_encode( array('success'=>false, 'message'=>'Missing CronID') ) );
}

$sql = "SELECT * FROM `ukmtv` WHERE `id` = '". (int) $_GET['cron_id'] ."';";
$res = $db->query( $sql );
$cron = $res->fetch_assoc();

die( json_encode( array('success'=>true, 'data'=> $cron ) ) );
