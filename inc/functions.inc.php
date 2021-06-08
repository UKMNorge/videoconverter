<?php
function ukmtv_update($field, $status, $id) {	
	logg('DB: '. "UPDATE `ukmtv` SET `".$field."` = '".$status."' WHERE `id` = '".$id."'");
	
	mysqli_query("UPDATE `ukmtv` SET `".$field."` = '".$status."' WHERE `id` = '".$id."'") or die(mysqli_error());
}

function logg( $message ) {
    error_log(LOG_SCRIPT_NAME .' '. CRON_ID .': '. $message );
} 

function notify( $message ) {
	logg( $message );
	if( defined('CRON_ID') && is_numeric( CRON_ID ) ) {
		mysqli_query("UPDATE `ukmtv` SET `admin_notice` = 'true' WHERE `id` = '". CRON_ID ."'") or die(mysqli_error());
	}
    
    error_log('SERVER ADMIN NOTIFICATION: cid'. CRON_ID .': '. $message );
}
