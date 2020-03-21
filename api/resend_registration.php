<?php

/**
 * Resend registration package to UKM.no
 * 
 * In case converting is completed before user press save
 */

require_once('UKMconfig.inc.php');
require_once('../inc/config.inc.php');
require_once('../inc/functions.inc.php');
require_once('../inc/curl.class.php');

if (!isset($_GET['cronId'])) {
    die(json_encode(false));
}

// FIND REQUESTED CRON
$res = mysql_query(
    "SELECT * 
    FROM `ukmtv` 
    WHERE `id` = '" . intval($_GET['cronId']) . "'
    AND `status_first_convert` = 'complete'
    LIMIT 1"
);
if( !$res ) {
    die(json_encode(false));
}

$cron = mysql_fetch_assoc($res);
$register = new UKMCURL();
$register->post($cron);
$register->request('https://api.' . UKM_HOSTNAME . '/video:registrer/' . $cron['cronId']);
die(json_encode(true));
