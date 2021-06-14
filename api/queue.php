<?php

use UKMNorge\Videoconverter\Database\Query;

require_once('../inc/autoloader.inc.php');
require_once('../inc/headers.inc.php');

$convert_queue = [
	'Crashed' => [],
	'Transferred' => [],
	'FirstConvert' => [],
	'FinalConvert' => [],
	'Archive' => []
];

// ANTALL FIRST-CONVERT I KØ
$query = new Query(
	"SELECT `id`,
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
	ORDER BY `id` ASC"
);
$res = $query->getResults();

while( $data = Query::fetch( $res ) ) {
	if( 	'converting' == $data['status_progress'] && 'complete' == $data['status_final_convert'] ) {
		$group = 'Archive';
	} 
	elseif( 'converting' == $data['status_progress'] && 'complete' == $data['status_first_convert'] ) {
		$group = 'FinalConvert';
	}
	elseif( 'registered' == $data['status_progress'] ) {
		$group = 'FirstConvert';
	} 
	else {
		$group = ucfirst( $data['status_progress'] );
	}


	if( !isset( $convert_queue[ $group ] ) ) {
		$convert_queue[ $group ] = [];		
	}
	
	$convert_queue[ $group ][] = $r;
}

die( json_encode( $convert_queue ) );
