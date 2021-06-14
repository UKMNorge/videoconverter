<?php

use UKMNorge\Videoconverter\Converter;
use UKMNorge\Videoconverter\Jobb\Flytt;

ini_set("log_errors", 1);
ini_set('display_errors', false);

require_once('inc/autoloader.php');
require_once('inc/headers.inc.php');
require_once('inc/jQupload_handler.inc.php');

################################################
## END WITH SUCCESS IF OPTIONS-REQUEST.
## Prevents logging "error with file size"
if( $_SERVER['REQUEST_METHOD'] == 'OPTIONS' ) {
	die('success');
}

error_log('UPLOADING TO: '. Converter::DIR_TEMP . Flytt::INBOX);

################################################
## ACTUALLY PERFORM UPLOAD 
$upload_handler = new UploadHandler(array('upload_dir' => Converter::DIR_TEMP . Flytt::INBOX));

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
## CHECK SUBMITTED FILE IS VIDEO-FILE (MIME-TYPE)
$filetype_matches = null;
$returnValue = preg_match('^video\\/(.)+^', $data_object->type, $filetype_matches);

if(sizeof($filetype_matches) == 0) {
	$error = new stdClass;
	$error->success = false;
	$error->message = 'Videoopplasteren tar kun i mot videofiler!';
	die(json_encode($error));
}
	
###################################################
## CREATE RETURN-OBJECT FOR JQUERY UPLOADER
$data->files[0] = $data_object;
$data->success = true;

die(json_encode($data));