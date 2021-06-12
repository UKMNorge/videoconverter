<?php

use UKMNorge\Videoconverter\Convert\First;
use UKMNorge\Videoconverter\Trigger;

require_once('../inc/autoloader.php');

// SCRIPT WILL FINISH IF USER LEAVES THE PAGE
ignore_user_abort(true);

# Kjør kun én om gangen (hvis ikke stacker de, og serveren dør)
if( First::isRunning() ) {
    die('Already converting one first-convert job. Awaiting that one');
}

# Hvis vi har noe som skal gjøres, gjør det
if( First::hasTodo() ) {
    First::startNext();
    Trigger::nextSecondConvert();
}

echo 'Success!';