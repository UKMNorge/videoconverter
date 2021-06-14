<?php

/**
 * Resend registration package to UKM.no
 * 
 * In case converting is completed before user press save
 */

use UKMNorge\Videoconverter\Jobb;
use UKMNorge\Videoconverter\Store\Store;

require_once('../inc/autoloader.php');
require_once('../inc/headers.inc.php');

if (!isset($_GET['cronId'])) {
    die(json_encode(false));
}

# Send registreringen på nytt
$jobb = new Jobb((int)$_GET['cronId']);
Store::register($jobb);

die(json_encode(true));
