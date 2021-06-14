<?php

use UKMNorge\Videoconverter\Converter;
use UKMNorge\Videoconverter\Jobb;
use UKMNorge\Videoconverter\Utils\Logger;

require_once('../inc/autoloader.php');
require_once('../inc/headers.inc.php');

if (!isset($_GET['id']) || !isset($_GET['hash'])) {
	die('Mangler parametre');
}

Logger::setId('API_LOG');

try {
	$jobb = new Jobb($_GET['id']);
} catch (Exception $e) {
	die(Logger::log('Beklager, kunne ikke hente jobb ' . $jobb->getId()));
}

if ($_GET['hash'] !== md5('log' . $jobb->getFil()->getNavn() . UKM_VIDEOSTORAGE_UPLOAD_KEY . $jobb->getId())) {
	die(Logger::log('Ugyldig hash'));
}

// OK - hent logg
if (!file_exists(Converter::DIR_BASE . Logger::DIR_LOG  . 'cron_' . $jobb->getId() . '.log')) {
	die(Logger::log('Logg-fil finnes ikke'));
}

echo '<h3>Logg for ' . $jobb->getId() . '</h3>';
echo file_get_contents(Converter::DIR_BASE . Logger::DIR_LOG . 'cron_' . $jobb->getId() . '.log');