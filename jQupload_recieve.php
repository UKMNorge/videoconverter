<?php
ini_set("log_errors", 1);
ini_set("error_log", dirname(__FILE__).'/error.log');
ini_set('display_errors', 0);

require_once('inc/jQupload_handler.inc.php');
require_once('UKMconfig.inc.php');
require_once('inc/config.inc.php');

################################################
## SET ALL HEADERS
require_once('inc/headers.inc.php');

################################################
## END WITH SUCCESS IF OPTIONS-REQUEST.
## Prevents logging "error with file size"
if( $_SERVER['REQUEST_METHOD'] == 'OPTIONS' ) {
	die('success');
}

error_log('UPLOADED: '. DIR_TEMP_UPLOAD);
################################################################################################
## CHECK FOR, AND SAVE "STATIC INFOS" (STUPID STUFF, NO CALCULATIONS AND SO ON)
################################################################################################
	################################################
	## ACTUALLY PERFORM UPLOAD 
	$upload_handler = new UploadHandler(array('upload_dir' => DIR_TEMP_UPLOAD));

	################################################
	## GET THE DATA ARRAY FOR FURTHER MANIPULATING
	$data = json_decode($upload_handler->get_body());
	$data_object = $data->files[0];

	if(!empty($data_object->error)) {
		$error = new stdClass;
		$error->success = false;
		$error->message = 'Det er en feil med filen. Dette kan være en feil i nettleseren din, eller fordi filen er skadet og inneholder feil informasjon om egen filstørrelse.';
		$error->data = $data;
		die(json_encode($error));
	}
	
	if(empty($data_object->size)) {
		$error = new stdClass;
		$error->success = false;
		$error->message = 'Det er en feil med filstørrelsen. Dette kan være en feil i nettleseren din, eller fordi filen er skadet og inneholder feil informasjon om egen filstørrelse.';
		$error->data = $data;
		die(json_encode($error));
	}
	
	################################################
	## CHECK FOR ALL REQUIRED POST-VALUES
	if(!isset($_POST['season']) ||
	   !isset($_POST['pl_id']) ||
	   !isset($_POST['type']) ||
	   !isset($_POST['b_id']) ||
	   !isset($_POST['blog_id'])
	   ) {
		$error = new stdClass;
		$error->success = false;
		$error->message = 'Opplasteren sendte ikke med alle POST-verdier (kontakt UKM Norge support, dette er en systemfeil)';
		$error->method = $_SERVER["REQUEST_METHOD"];
		$error->request = $_REQUEST;
		$error->post = $_POST;
		$error->get = $_GET;
		die(json_encode($error));
	}
	
	################################################
	## CHECK SUBMITTED FILE IS VIDEO-FILE (MIME-TYPE)
	$filetype_matches = null;
	$returnValue = preg_match('^video\\/(.)+^', $data_object->type, $filetype_matches);
	
	if(sizeof($filetype_matches) == 0) {
		$error = new stdClass;
		$error->success = false;
		$error->message = 'Videoopplasteren tar kun i mot videofiler!';
		die(json_encode($error));
	}

	################################################
	## SET POST VARS AS VARS	
	$SEASON		= $_POST['season'];
	$PL_ID		= $_POST['pl_id'];
	$TYPE 		= $_POST['type'];
	$B_ID 		= $_POST['b_id'] == 0 ? '0' : (string)$_POST['b_id'];
	$BLOG_ID	= $_POST['blog_id'];
	
	################################################
	## CREATE DATABASE ROW TO GET CRON_ID
	## (MUST BE UPDATED AS CRON_ID IS CRITICAL PART
	##  OF FILENAME)
	
	error_log('- Register with database');
	$insert = "INSERT INTO `ukmtv`
				   (`season`, `pl_id`, `type`, `b_id`, `blog_id`, `status_progress`)
			VALUES ('$SEASON', '$PL_ID', '$TYPE', '$B_ID', '$BLOG_ID', 'registering');";
	$res = $db->query($insert);
	
	if($res == false) {
		error_log('\n\nERROR: INSERT INTO DATABASE FAILED. FAULT: '.$db->error .'. \nEXITING');
		$data->success = false;
		$data->error = $db->error;
		die(json_encode($data));
	}
	$CRON_ID = $db->insert_id;
	error_log('- CRON_ID: '. $CRON_ID);
	
################################################################################################
## START VIDEOTECHNICAL CALCULATIONS (PREPARATIONS FOR CONVERT)
################################################################################################

	################################################
	## CALCULATE FILE EXTENSION OF UPLOADED FILE
	$file_ext = strtolower(substr($data_object->name, strrpos($data_object->name, '.')));

	################################################
	## CALCULATE NEW FILENAME OF FILE
	$file_name = $SEASON .'_'. $PL_ID .'_'. $TYPE .'_'. $B_ID .'_cron_' . $CRON_ID . $file_ext;
	$file_path = $SEASON .'/'. $PL_ID .'/'. $TYPE .'/';
			
	###################################################
	## CALCULATE THE REAL WIDTH AND HEIGHT BASED ON UPLOADED FILE
	error_log('- Calc dimensions');
	$file_uploaded = DIR_TEMP_UPLOAD.$data_object->name;
	$probe_width = "ffprobe -show_streams '$file_uploaded' 2>&1 | grep ^width | sed s/width=//";
	$probe_height = "ffprobe -show_streams '$file_uploaded' 2>&1 | grep ^height | sed s/height=//";
	$probe_duration = "ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 '$file_uploaded'";
    $probe_format = "ffprobe -show_entries stream=pix_fmt -of default=noprint_wrappers=1:nokey=1 '$file_uploaded'";
	$file_width = exec($probe_width);
	$file_height = exec($probe_height);
	$file_duration = (int) exec($probe_duration);
	$pixel_format = exec( $probe_format );
	
	error_log(
		'- WIDTH: ' .
		var_export( $file_width, true ) .
		' HEIGHT: '.
		var_export( $file_height, true ) .
		' PIXEL_FORMAT: ' .
		var_export( $pixel_format, true )
	);
	error_log('- DURATION: ' . var_export( $file_duration, true ) );

	if( empty($file_width) && empty($file_height)) {
		$data = new stdClass();
		$data->success = false;
		$data->error = "Mangler både filbredde og -høyde! Kan indikere at ffmpeg ikke er installert!";
		error_log($data->error);
		die(json_encode($data));
	}
    #$pixel_format = '';
	###################################################
	## MOVE FILE TO CONVERT-DIRECTORY
	rename($file_uploaded, DIR_TEMP_CONVERT.$file_name);
			
	###################################################
	## UPDATE DATABASE, SET READY FOR CONVERT
	$sql = "UPDATE 
				`ukmtv` 
			SET 
				`file_name` 	= '$file_name',
				`file_path` 	= '$file_path',
				`file_type` 	= '$file_ext',
				`file_width` 	= '$file_width',
				`file_height` 	= '$file_height',
				`file_duration` = '$file_duration',
				`status_progress`='registered',
                `pixel_format`  = '$pixel_format'
			WHERE
				`id` = '". $CRON_ID ."' LIMIT 1";
	$res = $db->query($sql);
	if($res === false) {
		error_log("Failed to update database with file data: ".$db->error);
		die($db->error);
	}
	
	###################################################
	## CREATE RETURN-OBJECT FOR JQUERY UPLOADER
	$data_object->cron_id = $CRON_ID;
	$data->files[0] = $data_object;
	$data->success = true;
	
	error_log('- SUCCESS!');
	error_log('- TRIGGER CONVERT');
	
	require_once('inc/curl.class.php');
	$store = new UKMCURL();
	$store->timeout(2);
	$store->request('https://videoconverter. ' . UKM_HOSTNAME . '/cron/convert_first.cron.php');
	error_log('- COMPLETE');
	die(json_encode($data));
?>
