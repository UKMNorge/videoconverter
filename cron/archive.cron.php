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
        WHERE `status_progress` = 'archive'
        AND `status_archive` = 'complete'
        AND `id` > 10000
        ORDER BY `id` DESC
        LIMIT 1";

$res = mysql_query( $sql );
$cron = mysql_fetch_assoc( $res );
if(!$cron)
    die('Nothing to store!');

define('LOG_SCRIPT_NAME', 'VIDEO ARCHIVE STORAGE');
define('CRON_ID', $cron['id']);
logg('START');

// Settings status to transferring
// End of script will set status back to
// a) converting (if status_final_convert not is complete)
// b) archive (if status_final_convert is complete)
// Script convert_final.cron will follow up on case a
// Script archive.cron will follow up on case b
#ukmtv_update('status_progress', 'transferring', $cron['id']);

ini_set('display_errors', true);
require_once('../inc/smartcore.fileCurl.php');
require_once('../inc/curl.class.php');
require_once('../inc/config_vars.inc.php');

logg('FETCH METADATA from api.ukm.no');

// Initiate final convert if possible (script will determine whether to process or not)
require_once('../inc/curl.class.php');
$store = new UKMCURL();
$store->timeout(8);
$apiAnswer = $store->request('http://api.' . UKM_HOSTNAME . '/video:info/'. CRON_ID);

$ARCHIVE_DIR = DIR_FINAL_ARCHIVE . $apiAnswer->path->dir;
logg('CREATE ARCHIVE DIR: '. $ARCHIVE_DIR );
if( !file_exists( $ARCHIVE_DIR ) ) {
	mkdir( $ARCHIVE_DIR, 0777, true);
}

logg('Write metadata to file');
$metadatafile = $ARCHIVE_DIR . $apiAnswer->path->filename.'.metadata.txt';

@unlink( $metadatafile );
$fileHandle = fopen( $metadatafile, 'w');
writeMetaData($fileHandle, $apiAnswer );
fclose( $fileHandle );
logg('Metadata written');

logg('ARCHIVE FILE: Send video to archive folder');
copy( $file_store_archive, $ARCHIVE_DIR . $apiAnswer->path->filename.'.mp4');

logg('ARCHIVE FILE: Send image to archive folder');
copy( $file_store_image, $ARCHIVE_DIR . $apiAnswer->path->filename.'.jpg');

logg('Update DB');
ukmtv_update('status_progress', 'complete', $cron['id']);
unlink( $file_log_fp_archive );
unlink( $file_log_sp_archive );
unlink( $file_log_image );
unlink( $file_store_archive );
logg('CRON END');


/////////////////////////// FUNCTIONS ///////////////////////////

function writeMetaData($fileHandle, $object, $indent=0 ) {
	if( is_object( $object ) or is_array( $object ) ) {
		foreach( $object as $key => $value ) {
			if( is_object( $value ) or is_array( $value ) ) {
				echo str_repeat(' &nbsp; ', $indent) . strtoupper( $key ) . ': <br />';
				fwrite( $fileHandle, str_repeat(' ', $indent) . strtoupper( $key ) . ': ' ."\r\n");
				writeMetaData($fileHandle, $value, ($indent+1) );
			} else {
				echo str_repeat(' &nbsp; ', $indent) . ucfirst( $key ) .': '. $value .'<br />';
				fwrite( $fileHandle, str_repeat(' ', $indent) . ucfirst( $key ) .': '. $value  ."\r\n" );
			}
		}
	}
}