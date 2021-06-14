<?php

use UKMNorge\Videoconverter\Jobb;

require_once('../inc/autoloader.php');
require_once('../inc/headers.inc.php');

if( !isset( $_GET['cron_id'] ) ) {
	die( json_encode( array('success'=>false, 'message'=>'Missing CronID') ) );
}

$jobb = new Jobb((int) $_GET['cron_id']);

die( json_encode( array('success'=>true, 'data'=> $jobb->getDatabaseData() ) ) );
