<?php
function ukmtv_update($field, $status, $id) {	
	mysql_query("UPDATE `ukmtv` SET `".$field."` = '".$status."' WHERE `id` = '".$id."'") or die(mysql_error());
}

function logg( $message ) {
    error_log(LOG_SCRIPT_NAME .' '. CRON_ID .': '. $message );
}

function notify( $message ) {
    error_log('SERVER ADMIN NOTIFICATION: cid'. CRON_ID .': '. $message );
}
