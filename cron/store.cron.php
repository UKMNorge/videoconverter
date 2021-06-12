<?php

use UKMNorge\Videoconverter\Store\Store;

require_once('../inc/autoloader.php');

// SCRIPT WILL FINISH IF USER LEAVES THE PAGE
ignore_user_abort(true);

# Lagt til 15. mai 2016 for å overføre avslutningsshow UKM Oslo 2016 - stor fil
ini_set('max_execution_time', Store::MAX_TRANSFER_TIME);


if( Store::isRunning() ) {
    die('Already transferring one film. Waiting for this to finish');
}

Store::startNext();