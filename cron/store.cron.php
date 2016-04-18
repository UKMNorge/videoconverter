<?php
// SCRIPT WILL FINISH IF USER LEAVES THE PAGE
// CONVERT.INC.PHP TRIGGERS THIS CRON BY CURL(timeout: 2)
ignore_user_abort(true);

require_once('UKMconfig.inc.php');
require_once('../inc/config.inc.php');
require_once('../inc/functions.inc.php');

// IF ALREADY TRANSFERRING ONE, TAKE A NAP
$test = "SELECT `id` FROM `ukmtv`
        WHERE `status_progress` = 'transferring'
        ORDER BY `id` ASC
        LIMIT 1";
$testres = mysql_query( $test );
if( mysql_num_rows( $testres ) > 0 )
    die('Already transferring one film. Waiting for this to finish');

// FIND NEXT TRANSFERJOB
$sql = "SELECT * FROM `ukmtv`
        WHERE `status_progress` = 'store'
        ORDER BY `id` ASC
        LIMIT 1";

$res = mysql_query( $sql );
$cron = mysql_fetch_assoc( $res );
if(!$cron)
    die('Nothing to store!');

define('LOG_SCRIPT_NAME', 'VIDEO STORAGE');
define('CRON_ID', $cron['id']);
ini_set("error_log", DIR_LOG . 'cron_'. CRON_ID .'.log');
logg('START');

// Settings status to transferring
// End of script will set status back to
// a) converting (if status_final_convert not is complete)
// b) archive (if status_final_convert is complete)
// Script convert_final.cron will follow up on case a
// Script archive.cron will follow up on case b
ukmtv_update('status_progress', 'transferring', $cron['id']);

ini_set('display_errors', true);
require_once('../inc/smartcore.fileCurl.php');
require_once('../inc/curl.class.php');
require_once('../inc/config_vars.inc.php');

$transfer = array('hd' => 'HD', 'mobile' => 'MOB', 'image' => 'IMG');

$ERROR = false;
foreach( $transfer as $varname => $name ) {
    if( $ERROR ) {
        break;
    }
    logg($name .' FILE: Send to '. REMOTE_SERVER);

    // SIGN
    $file_hash = hash_file('sha256', ${'file_store_'.$varname} );
    $file_path = $cron['file_path'];
    $timestamp = time();

    $msg = "file_path=$file_path&file_hash=$file_hash&timestamp=$timestamp";
    $sign = hash_hmac('sha256', $msg, UKM_VIDEOSTORAGE_UPLOAD_KEY);

    $curl_request = new CurlFileUploader(${'file_store_'.$varname},                               // FILE TO SEND
                                         REMOTE_SERVER.'/receive.php',                            // SERVER TO RECEIVE (SCRIPT)
                                         'file',                                                  // NAME OF FILES-ARRAY
                                         array( 'file_name' => ${'file_name_output_'.$varname},   // NAME TO BE STORED AS
                                                'file_path' => $cron['file_path'],                // PATH TO BE STORED AT
                                                'file_hash' => $file_hash,                        // HASH OF LOCAL FILE
                                                'sign'      => $sign,                             // CONCAT SIGN OF ALL VALUES
                                                'timestamp' => $timestamp
                                              )
                                        );
    $response = $curl_request->UploadFile();

    logg($name .' FILE: RESPONSE');
    if($response[0]) {
        $report = json_decode( $response[1] );

        if($report->success) {
            logg($name .' FILE: SUCCESS!');
        } else {
            $ERROR = true;
            logg($name .' FILE: FAILURE (Storage server)');
            notify('Unable to store '. $name .' file. Storage server failure');
        }
        logg($name .' FILE: '. $report->file_name . ' storing @ ' . $report->file_abs_path  );
    } else {
        $ERROR = true;
        logg($name .' FILE: Failed to send file');
        notify('Unable to reach storage server. '. $name .' file not sent');
    }
}

if( $ERROR ) {
    logg('FAILED TO STORE');
    notify('Files converted, but one or more not sent to server');
    ukmtv_update('status_progress', 'crashed', $cron['id']);
} else {
    ukmtv_update('status_progress', 'transferred', $cron['id']);
    logg('NOTIFY UKM.no');
    logg('http://api.' . UKM_HOSTNAME . '/video:registrer/'.$cron['id']);
    foreach( $cron as $key => $val ) {
        logg( 'CURL POST:'.$key .' => '. var_export( $val, true ) );
    }

    $register = new UKMCURL();
    $register->post($cron);
    // SQLins kan ta tid mens serveren tar backup. La den få litt tid på natta
    if( date('G') < 5 ) {
		$register->timeout(20);
	} else {
	    $register->timeout(10);
	}
    $register->request('http://api.' . UKM_HOSTNAME . '/video:registrer/'.$cron['id']);

    foreach( $register as $key => $val ) {
        logg( 'CURL RESPONSE:'.$key .' => '. var_export( $val, true ) );
    }
    if( isset( $register->data ) && isset( $register->data->success ) && $register->data->success ) {
        // SET READY FOR SECOND CONVERT / ARCHIVING
        // a) converting (if status_final_convert not is complete)
        // b) archive (if status_final_convert is complete)
        // Script convert_final.cron will follow up on case a
        // Script archive.cron will follow up on case b
        if($cron['status_final_convert'] != 'complete') {
            logg('NEXT STEP: converting (ready for final convert)');
            ukmtv_update('status_progress', 'converting', $cron['id']);
        } else {
            logg('NEXT STEP: archive (this was final convert, ready to archive)');
            ukmtv_update('status_progress', 'archive', $cron['id']);
        }

        unlink( $file_log_fp_hd );
        unlink( $file_log_fp_mobile );
        unlink( $file_log_sp_hd );
        unlink( $file_log_sp_mobile );
        unlink( $file_log_image );

        unlink( $file_store_hd );
        unlink( $file_store_mobile );
        unlink( $file_store_image );

        logg('CRON END');
        // CONVERT-FILE WILL BE DELETED BY ARCHIVER
    } else {
        logg('FAILED to register with UKM-TV');
        notify('FAILED to register with UKM-TV. Everything OK except this');
    }
}
