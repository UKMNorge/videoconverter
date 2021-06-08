<?php

use UKMNorge\Videoconverter\Eier;
use UKMNorge\Videoconverter\Film;
use UKMNorge\Videoconverter\Job;

ini_set("log_errors", 1);
ini_set("error_log", dirname(__FILE__).'/error.log');
ini_set('display_errors', 0);

require_once('UKMconfig.inc.php');
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
	

NÅR LASTET OPP: REQUIRE registrer.php

###################################################
## CREATE RETURN-OBJECT FOR JQUERY UPLOADER
$data_object->cron_id = $CRON_ID;
$data->files[0] = $data_object;
$data->success = true;
die(json_encode($data));