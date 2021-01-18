<?php
require_once('UKMconfig.inc.php');
require_once('../inc/headers.inc.php');
require_once('../inc/config.inc.php');

$convert_queue = array();
$convert_queue['Crashed'] = array();
$convert_queue['Transferred'] = array();
$convert_queue['FirstConvert'] = array();
$convert_queue['FinalConvert'] = array();
$convert_queue['Archive'] = array();

// ANTALL FIRST-CONVERT I KØ
$sql = "SELECT	`id`,
				`status_progress`,
				`status_first_convert`,
				`status_final_convert`,
				`status_archive`,
				`file_width`,
				`file_height`,
				`admin_notice`,
				`file_name`, 
				`touch`
		FROM `ukmtv`
		WHERE `status_progress` != 'complete'
		AND `status_progress` != 'does_not_exist'
		ORDER BY `id` ASC";
$res = $db->query( $sql );

while( $r = $res->fetch_assoc() ) {
	
	
	if( 	'converting' == $r['status_progress'] && 'complete' == $r['status_final_convert'] ) {
		$group = 'Archive';
	} 
	elseif( 'converting' == $r['status_progress'] && 'complete' == $r['status_first_convert'] ) {
		$group = 'FinalConvert';
	}
	elseif( 'registered' == $r['status_progress'] ) {
		$group = 'FirstConvert';
	} 
	else {
		$group = ucfirst( $r['status_progress'] );
	}


	if( !isset( $convert_queue[ $group ] ) ) {
		$convert_queue[ $group ] = array();		
	}
	
	$convert_queue[ $group ][] = $r;
}

die( json_encode( $convert_queue ) );
