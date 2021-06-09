<?php

use UKMNorge\Videoconverter\Convert\First;

require_once('inc/autoloader.php');

// SCRIPT WILL FINISH IF USER LEAVES THE PAGE
ignore_user_abort(true);

if( First::isRunning() ) {
    die('Already converting one first-convert job. Awaiting that one');
}

First::startNext();