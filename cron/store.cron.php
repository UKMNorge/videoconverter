<?php
// SCRIPT WILL FINISH IF USER LEAVES THE PAGE
// CONVERT.INC.PHP TRIGGERS THIS CRON BY CURL(timeout: 2)
ignore_user_abort(true);

require_once('UKMconfig.inc.php');
require_once('../inc/config.inc.php');
require_once('../inc/functions.inc.php');

function logg( $message ) {
    error_log('VIDEO STORAGE '. CRON_ID .': '. $message );
}

function notify( $message ) {
    error_log('SERVER ADMIN NOTIFICATION: cid'. CRON_ID .': '. $message );
}

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

define('CRON_ID', $cron['id']);
logg('PROCESS STORAGE');	

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

$file_name_input			= $cron['file_name'];
$file_name_output_raw		= str_replace($cron['file_type'], '', $file_name_input);
$file_name_output_hd		= $file_name_output_raw . $file_extension_hd;
$file_name_output_mobile	= $file_name_output_raw . $file_extension_mobile;
$file_name_output_image		= $file_name_output_raw . '.jpg';

// Full paths to ffmpeg files
$file_input				= DIR_TEMP_CONVERT . $file_name_input;	

$file_output_hd			= DIR_TEMP_CONVERTED . $file_name_output_hd;
$file_output_mobile		= DIR_TEMP_CONVERTED . $file_name_output_mobile;

$file_store_hd			= DIR_TEMP_STORE . $file_name_output_hd;
$file_store_mobile		= DIR_TEMP_STORE . $file_name_output_mobile;
$file_store_image		= DIR_TEMP_STORE . $file_name_output_raw.'.jpg';

$file_log_raw_hd		= DIR_TEMP_LOG . $file_name_output_raw . $file_id_hd;
$file_log_raw_mobile	= DIR_TEMP_LOG . $file_name_output_raw . $file_id_mobile;
$file_log_raw_image		= DIR_TEMP_LOG . $file_name_output_raw;

$file_log_fp_hd			= $file_log_raw_hd . '_firstpass.txt';
$file_log_fp_mobile		= $file_log_raw_mobile . '_firstpass.txt';
$file_log_sp_hd			= $file_log_raw_hd . '_secondpass.txt';
$file_log_sp_mobile	 	= $file_log_raw_mobile . '_secondpass.txt';
$file_log_image		 	= $file_log_raw_image . '_image.txt';

$transfer = array('hd' => 'HD', 'mobile' => 'MOB', 'image' => 'IMG');

foreach( $transfer as $varname => $name ) {
    logg($name .' FILE: Send to '. REMOTE_SERVER);
    $curl_request = new CurlFileUploader(${'file_store_'.$varname},								  // FILE TO SEND
    			    					 REMOTE_SERVER.'/receive.php',						      // SERVER TO RECEIVE (SCRIPT)
                                         'file',											      // NAME OF FILES-ARRAY
                                         array( 'file_name' => ${'file_name_output_'.$varname},	  // NAME TO BE STORED AS
    			    				 			'file_path' => $cron['file_path']			      // PATH TO BE STORED AT
                                              )
                                        );
    $response = $curl_request->UploadFile();

    logg($name .' FILE: RESPONSE');
    if($response[0]) {
    	$report = json_decode( $response[1] );
    
    	if($report->success) {
        	logg($name .' FILE: SUCCESS!');
    	} else {
        	logg($name .' FILE: FAILURE (Storage server)');
        	notify('Unable to store '. $name .' file. Storage server failure');
    	}
    	logg($name .' FILE: '. $report->file_name . ' storing @ ' . $report->file_abs_path	);
    } else {
        logg($name .' FILE: Failed to send file');
        notify('Unable to reach storage server. '. $name .' file not sent');
    }
}

logg('NOTIFY UKM.no');
logg('http://api.' . UKM_HOSTNAME . '/video:registrer/'.$cron['id']);
foreach( $cron as $key => $val ) {
    logg( 'CURL POST:'.$key .' => '. var_export( $val, true ) );
}

$register = new UKMCURL();
$register->post($cron);
$register->request('http://api.' . UKM_HOSTNAME . '/video:registrer/'.$cron['id']);

foreach( $register->data as $key => $val ) {
    logg( 'CURL RESPONSE:'.$key .' => '. var_export( $val, true ) );
}

// SET READY FOR SECOND CONVERT / ARCHIVING
// a) converting (if status_final_convert not is complete)
// b) archive (if status_final_convert is complete)
// Script convert_final.cron will follow up on case a
// Script archive.cron will follow up on case b
if($cron['status_final_convert'] != 'complete') {
    logg('NEXT STEP: converting (ready for final convert)')
	ukmtv_update('status_progress', 'converting', $cron['id']);
} else {
    logg('NEXT STEP: archive (this was final convert, ready to archive)')
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