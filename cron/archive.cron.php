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
        ORDER BY `id` ASC
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
ukmtv_update('status_progress', 'transferring', $cron['id']);

ini_set('display_errors', true);
require_once('../inc/smartcore.fileCurl.php');
require_once('../inc/curl.class.php');
require_once('../inc/config_vars.inc.php');

$transfer = array('archive' => 'Arkiv','image' => 'IMG');

$ERROR = false;
foreach( $transfer as $varname => $name ) {
    if( $ERROR ) {
        break;
    }
    logg($name .' FILE: Send to archive folder');
    // BURDE VÆRE ET SKIKKELIG NAVN, I EN MENNESKELIG LESBAR STRUKTUR
    // KOMMUNISER MED UKM-TV
    // LAGRE EXIF-DATA
    $storage_filename = DIR_FINAL_ARCHIVE . ${'file_name_output_'.$varname};
    copy( ${'file_store_'.$varname}, $storage_filename);
}

if( $ERROR ) {
    logg('FAILED TO STORE');
    notify('Files converted, but one or more not sent to archive server');
    ukmtv_update('status_progress', 'chrashed', $cron['id']);
} else {
    ukmtv_update('status_progress', 'complete', $cron['id']);
    unlink( $file_log_fp_archive );
    unlink( $file_log_sp_archive );
    unlink( $file_log_image );
    unlink( $file_store_archive );
	logg('CRON END');
    // TODO: CONVERT-FILE WILL BE DELETED BY ARCHIVER
}
