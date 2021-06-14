<?php

use UKMNorge\Videoconverter\Converter;
use UKMNorge\Videoconverter\Database\Update;
use UKMNorge\Videoconverter\Jobb;
use UKMNorge\Videoconverter\Utils\Logger;

require_once('../inc/autoloader.php');
require_once('../inc/headers.inc.php');

# Sjekk at vi har nødvendig info
if (empty($_GET['cron_id']) || 0 == (int) $_GET['cron_id']) {
	die(json_encode(array('success' => false, 'message' => 'CronID mangler eller er feil')));
}


Logger::setId('API_RESET_ON_UPLOAD');
Logger::setCron((int) $_GET['cron_id']);
Logger::log('Reset on upload');

$jobb = new Jobb((int) $_GET['cron_id']);

if ($jobb->getDatabaseData('status_first_convert') != 'complete' && $jobb->getStatus() != 'transferred') {

	Logger::log('Trenger ikke reset - mest sannsynlig alt ok');
	die(json_encode(array('success' => true, 'message' => 'Fant ingen film som trenger reset - dette kan bety at alt er ok')));
}

Logger::log('Trenger reset');
$sql_upd = "UPDATE `ukmtv` 
			SET `status_progress` = 'store', 
				`status_first_convert` = 'complete', 
				`status_final_convert` = NULL, 
				`status_archive` = NULL 
			WHERE `id` = '" . $CRON_ID . "' 
			LIMIT 1
			";
$update = new Update(
	Converter::TABLE,
	[
		'id' => $jobb->getId()
	]
);
$update->add('status_progress', 'store');
$update->add('status_first_convert', 'complete');
$update->add('status_final_convert', NULL);
$update->add('status_archive', NULL);

Logger::log('SQL: ' . (str_replace(array("\r", "\n", "\t", '<br />'), '', $update->debug())));

if (!$res) {
	Logger::log('FEILET');
	die(json_encode(array('success' => false, 'message' => 'Kunne ikke oppdatere database')));
}

Logger::log('Fil resatt');
die(json_encode(array('success' => true, 'message' => 'Reset-spørring kjørt')));
