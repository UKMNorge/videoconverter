<?php
require_once('UKMconfig.inc.php');
require_once('../inc/headers.inc.php');

if( !isset( $_GET['id'] ) || !isset( $_GET['hash'] ) || !isset( $_GET['action'] ) ) {
	die('Mangler parametre');
}

$ID = $_GET['id'];
$HASH = $_GET['hash'];
$ACTION = $_GET['action'];

require_once('../inc/config.inc.php');

$test = "SELECT `file_name` FROM `ukmtv` WHERE `id` = '". $ID ."'";
$testresult = $db->query( $test );
if( $testresult != false && $testresult->num_rows > 0 ) {
	$row = $testresult->fetch_assoc();
	$hashtest = md5( $ACTION . $row['file_name'] . UKM_VIDEOSTORAGE_UPLOAD_KEY . $ID );
	if( $hashtest !== $HASH ) {
		die('Ugyldig hash');
	}
	
	define('CRON_ID', $ID );
	define('LOG_SCRIPT_NAME', 'API CHANGE STATUS');
	ini_set("error_log", DIR_LOG . 'cron_'. CRON_ID .'.log');
	require_once('../inc/functions.inc.php');

	switch( $ACTION ) {
		case 'delete':
			require_once('change_status/delete.php');
			die();
		case 'store':
			require_once('change_status/store.php');
			die();
		case 'registered':
			require_once('change_status/registered.php');
			die();
	}
	
	die('Ukjent handling');
}
die('Cron ikke funnet');