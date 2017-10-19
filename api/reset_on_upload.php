<?php
require_once('UKMconfig.inc.php');
require_once('../inc/headers.inc.php');
require_once('../inc/config.inc.php');
require_once('../inc/functions.inc.php');
	
$CRON_ID = $_GET['cron_id'];

if( empty( $CRON_ID ) || 0 == (int) $CRON_ID ) {
	die( json_encode(array('success'=>false, 'message'=>'CronID mangler eller er feil')) );
}

define('CRON_ID', $CRON_ID);
define('LOG_SCRIPT_NAME', 'RESETonUPLOAD');

$sql = "SELECT `id` 
		FROM `ukmtv`
		WHERE `status_progress` = 'transferred'
		AND `status_first_convert` = 'complete'
		AND `id` = '".$CRON_ID."'
		LIMIT 1";
$res = mysql_query( $sql );
$row = mysql_fetch_assoc( $res );
$db_id = $row['id'];

if( (int) $db_id != (int) $CRON_ID ) {
	logg('Fil trenger ikke reset - mest sannsynlig alt ok');
	die( json_encode(array('success'=>true, 'message'=>'Fant ingen låt som trenger reset - dette kan bety at alt er ok')) );
}

logg('Fil trenger reset');
$sql_upd = "UPDATE `ukmtv` 
			SET `status_progress` = 'store', 
				`status_first_convert` = 'complete', 
				`status_final_convert` = NULL, 
				`status_archive` = NULL 
			WHERE `id` = '". $CRON_ID ."' 
			LIMIT 1
			";
logg( str_replace(array("\r", "\n", "\t"), '', $sql_upd ) );
$res = mysql_query( $sql_upd );

if( !$res ) {
	logg( mysql_error() );
}
logg('Fil resatt');

die( json_encode(array('success'=>true, 'message'=>'Reset-spørring kjørt')) );