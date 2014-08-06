<?php
// SCRIPT WILL FINISH IF USER LEAVES THE PAGE
// CONVERT.INC.PHP TRIGGERS THIS CRON BY CURL(timeout: 2)
ignore_user_abort(true);

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

	
error_log('VIDEO STORAGE '. $cron['id'] .': Init cron, start processing');
echo '<h1>Storing cron '. $cron['id'] .'</h1>';

// Settings status to transferring
// End of script will set status back to
// a) converting (if status_final_convert not is complete)
// b) archive (if status_final_convert is complete)
// Script convert_final.cron will follow up on case a
// Script archive.cron will follow up on case b
ukmtv_update('status_progress', 'transferring', $cron['id']);

ini_set('display_errors', true);
require_once('../inc/smartcore.fileCurl.php');

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

error_log('VIDEO STORAGE '. $cron['id'] .': Send HD-file to '. REMOTE_SERVER);
$curl_hd = new CurlFileUploader($file_store_hd,										// FILE TO SEND
								 REMOTE_SERVER.'/recieve.php',						// SERVER TO RECIEVE (SCRIPT)
								 'file',											// NAME OF FILES-ARRAY
								 array( 'file_name' => $file_name_output_hd,		// NAME TO BE STORED AS
							 			'file_path' => $cron['file_path']			// PATH TO BE STORED AT
							 			)
							 	);
$upload_hd = $curl_hd->UploadFile();

error_log('VIDEO STORAGE '. $cron['id'] .': Send mobile-file to '. REMOTE_SERVER);
$curl_mobile = new CurlFileUploader($file_store_mobile,								// FILE TO SEND
									REMOTE_SERVER.'/recieve.php',					// SERVER TO RECIEVE (SCRIPT)
									'file',											// NAME OF FILES-ARRAY
									array( 'file_name' => $file_name_output_mobile,	// NAME TO BE STORED AS
										   'file_path' => $cron['file_path']		// PATH TO BE STORED AT
								 		 )
								   );
$upload_mobile = $curl_mobile->UploadFile();

error_log('VIDEO STORAGE '. $cron['id'] .': Send image-file to '. REMOTE_SERVER);
$curl_image = new CurlFileUploader( $file_store_image,								// FILE TO SEND
									REMOTE_SERVER.'/recieve.php',					// SERVER TO RECIEVE (SCRIPT)
									'file',											// NAME OF FILES-ARRAY
									array( 'file_name' => $file_name_output_image,	// NAME TO BE STORED AS
							 			   'file_path' => $cron['file_path']		// PATH TO BE STORED AT
							 			 )
							 	  );
$upload_image = $curl_image->UploadFile();

echo '<h2>File storage</h2>';

error_log('VIDEO STORAGE '. $cron['id'] .': SOME SORT OF ERROR HANDLE');
error_log('VIDEO STORAGE '. $cron['id'] .': UPLOAD HD');
if($upload_hd[0]) {
	$report = json_decode( $upload_hd[1] );
	if($report->success)
		echo '<strong>SUCCESS!</strong>: ';
	else
		echo '<strong>ERROR!!</strong>: '; 
	
	echo $report->file_name . '<br />'
			.' &nbsp; storing @ ' . $report->file_abs_path .'<br />';
	error_log('VIDEO STORAGE '. $cron['id'] .': '. $report->file_name . '- storing @ ' . $report->file_abs_path	);
}
error_log('VIDEO STORAGE '. $cron['id'] .': UPLOAD MOBILE');
if($upload_mobile[0]) {
	$report = json_decode( $upload_mobile[1] );
	if($report->success)
		echo '<strong>SUCCESS!</strong>: ';
	else
		echo '<strong>ERROR!!</strong>: '; 
	
	echo $report->file_name . '<br />'
			.' &nbsp; storing @ ' . $report->file_abs_path .'<br />';
	error_log('VIDEO STORAGE '. $cron['id'] .': '. $report->file_name . '- storing @ ' . $report->file_abs_path	);
}
error_log('VIDEO STORAGE '. $cron['id'] .': UPLOAD IMAGE');
if($upload_image[0]) {
	$report = json_decode( $upload_image[1] );
	if($report->success)
		echo '<strong>SUCCESS!</strong>: ';
	else
		echo '<strong>ERROR!!</strong>: '; 
	
	echo $report->file_name . '<br />'
			.' &nbsp; storing @ ' . $report->file_abs_path .'<br />';
	error_log('VIDEO STORAGE '. $cron['id'] .': '. $report->file_name . '- storing @ ' . $report->file_abs_path	);
}
error_log('VIDEO STORAGE '. $cron['id'] .': NOTIFY STORAGE SERVER');
error_log('VIDEO STORAGE '. $cron['id'] .': CRON URL');
error_log('VIDEO STORAGE '. $cron['id'] .': http://api.ukm.no/video:registrer/'.$cron['id']);
error_log('VIDEO STORAGE '. $cron['id'] .': CRON DATA');
error_log('VIDEO STORAGE '. $cron['id'] .': '. var_export($cron, true));
// NOTIFY UKM.no VIDEO IS CONVERTED AND TRANSFERRED TO STORAGE
require_once('../inc/curl.class.php');
$register = new UKMCURL();
$register->post($cron);
$register->request('http://api.ukm.no/video:registrer/'.$cron['id']);
echo '<h2>Registering with UKM.no</h2>';
echo $register->data;
error_log('VIDEO STORAGE '. $cron['id'] .': '. $register->data);

// SET READY FOR SECOND CONVERT / ARCHIVING
// a) converting (if status_final_convert not is complete)
// b) archive (if status_final_convert is complete)
// Script convert_final.cron will follow up on case a
// Script archive.cron will follow up on case b
if($cron['status_final_convert'] != 'complete') {
	ukmtv_update('status_progress', 'converting', $cron['id']);
} else {
	ukmtv_update('status_progress', 'archive', $cron['id']);
}

error_log('VIDEO STORAGE '. $cron['id'] .': CLEANUP');
echo '<h2>Cleanup</h2>';
echo '<h3>Log-files</h3>';
echo 'Delete '. $file_log_fp_hd .' <br />';
unlink( $file_log_fp_hd );
echo 'Delete '. $file_log_fp_mobile .' <br />';
unlink( $file_log_fp_mobile );
echo 'Delete '. $file_log_sp_hd .' <br />';
unlink( $file_log_sp_hd );
echo 'Delete '. $file_log_sp_mobile .' <br />';
unlink( $file_log_sp_mobile );
echo 'Delete '. $file_log_image .' <br />';
unlink( $file_log_image );


echo '<h3>Video-files</h3>';
echo 'Delete '. $file_store_hd .' <br />';
unlink( $file_store_hd );
echo 'Delete '. $file_store_mobile .' <br />';
unlink( $file_store_mobile );
echo 'Delete '. $file_store_image .' <br />';
unlink( $file_store_image );

error_log('VIDEO STORAGE '. $cron['id'] .': CRON SUCCESS!');
// CONVERT-FILE WILL BE DELETED BY ARCHIVER