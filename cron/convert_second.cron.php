<?php

use UKMNorge\Videoconverter\Convert\First;
use UKMNorge\Videoconverter\Convert\Second;

require_once('inc/autoloader.php');

// SCRIPT WILL FINISH IF USER LEAVES THE PAGE
ignore_user_abort(true);

if( First::isRunning() ) {
    die('Already converting one first-convert job. Awaiting that one');
}

if( First::hasTodo() ) {
    die('There\'s a queue for first convert. Making content available is the top priority.');
}

Second::startNext();