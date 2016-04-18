<?php
function ukmtv_update($field, $status, $id) {	
	logg('DB: '. "UPDATE `ukmtv` SET `".$field."` = '".$status."' WHERE `id` = '".$id."'");
	
	mysql_query("UPDATE `ukmtv` SET `".$field."` = '".$status."' WHERE `id` = '".$id."'") or die(mysql_error());
}

function logg( $message ) {
	$file_name = DIR_LOG . 'cron_'. CRON_ID .'.log';
	$file_handle = fopen( $file_name, 'a');
	fwrite( $file_handle, "\r\n" . date('d.m H:i') .': '. $message );
	fclose( $file_handle );
    error_log(LOG_SCRIPT_NAME .' '. CRON_ID .': '. $message );
} 

function notify( $message ) {
	logg( $message );
	if( defined('CRON_ID') && is_numeric( CRON_ID ) ) {
		mysql_query("UPDATE `ukmtv` SET `admin_notice` = 'true' WHERE `id` = '". CRON_ID ."'") or die(mysql_error());
	}
    
    error_log('SERVER ADMIN NOTIFICATION: cid'. CRON_ID .': '. $message );
}
