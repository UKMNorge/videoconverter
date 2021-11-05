<?php

use UKMNorge\Videoconverter\Convert\First;
use UKMNorge\Videoconverter\Convert\Second;

require_once('../inc/autoloader.php');

// SCRIPT WILL FINISH IF USER LEAVES THE PAGE
ignore_user_abort(true);

if( First::isRunning() ) {
    die('Already converting one first-convert job. Awaiting that one');
}

if( First::hasTodo() ) {
    die('There\'s a queue for first convert. Making content available is the top priority.');
}

if( Second::isRunning() ) {
    die('A friend of mine\'s currently working on a second convert. I\'ll wait for him to finish');
}

# Hvis vi har noe som skal gjøres, gjør det
if( Second::hasTodo() ) {
    Second::startNext();
    #Trigger::nextSecondConvert();
    die('Success');
}

die('There\'s nothing for me to do 😭');