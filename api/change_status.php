<?php

use UKMNorge\Videoconverter\Jobb;
use UKMNorge\Videoconverter\Utils\Logger;

require_once('../inc/autoloader.php');
require_once('../inc/headers.inc.php');

# Sjekk at vi har påkrevde parametre
if (!isset($_GET['id']) || !isset($_GET['hash']) || !isset($_GET['action'])) {
	die('Mangler parametre');
}

# Prøv å hente inn jobbem
try {
	$jobb = new Jobb((int) $_GET['id']);
} catch (Exception $e) {
	die($e->getMessage());
}

# Sjekk at hash er gyldig
if ($_GET['hash'] !== md5($_GET['action'] . $jobb->getFil()->getNavn() . UKM_VIDEOSTORAGE_UPLOAD_KEY . $jobb->getId())) {
	die('Ugyldig hash');
}

# Klargjør logger
Logger::setId('API_CHANGE_STATUS');
Logger::setCron($jobb->getId());

header('Content-Type: application/json');

# Do the action
switch ($_GET['action']) {
	case 'delete':
		Logger::log('Slett jobben'); // aka does_not_exist
		$jobb->delete();
		break;
	case 'store':
		Logger::log('Restart lagringsprosess');
		$jobb->saveStatus('store');
		$jobb->resetAdminNotice();
		break;
	case 'registered':
		Logger::log('Restart jobben');
		$jobb->restart();
		break;
	default:
		die(Logger::log('Ukjent handling ' . $_GET['action']));
}

die(json_encode([
	'action' => $_GET['action'],
	'success' => true
]));
