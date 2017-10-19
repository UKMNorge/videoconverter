<?php
require_once('UKMconfig.inc.php');
require_once('../inc/headers.inc.php');

if( !isset( $_GET['id'] ) || !isset( $_GET['hash'] ) ) {
	die('Mangler parametre');
}

$ID = $_GET['id'];
$HASH = $_GET['hash'];

require_once('../inc/config.inc.php');

$test = "SELECT `file_name` FROM `ukmtv` WHERE `id` = '". $ID ."'";
$test = mysql_query( $test );
if( mysql_num_rows( $test ) > 0 ) {
	$row = mysql_fetch_assoc( $test );
	$hashtest = md5( 'log' . $row['file_name'] . UKM_VIDEOSTORAGE_UPLOAD_KEY . $ID );
	if( $hashtest !== $HASH ) {
		die('Ugyldig hash');
	}
	
	// OK - hent logg
	if( !file_exists( DIR_LOG .'cron_'. $ID .'.log' ) ) {
		die('Logg-fil finnes ikke');
	}
	echo '<h3>Logg for '. $ID .'</h3>';
	echo file_get_contents( DIR_LOG .'cron_'. (int) $ID .'.log' );
	die();
}
die('Cron ikke funnet');