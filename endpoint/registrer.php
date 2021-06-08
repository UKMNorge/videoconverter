<?php

use UKMNorge\Videoconverter\Eier;
use UKMNorge\Videoconverter\Film;
use UKMNorge\Videoconverter\Jobb;
use UKMNorge\Videoconverter\Trigger;
use UKMNorge\Videoconverter\Utils\Logger;

Logger::setId('REGISTRER');
Logger::log('Registrer ny konverteringsjobb');
try {
    $eier = new Eier(
        (int) $_POST['blog_id'],
        (int) $_POST['pl_id'],
        (int) $_POST['season']
    );

    $film = new Film(
        $_POST['type'],
        (int) $_POST['b_id']
    );
        
    $jobb = Jobb::registrer(
        $eier,
        $film, 
        $file
    );
} catch( Exception $e ) {
    Logger::log('EXCEPTION: '. $e->getMessage());
    throw $e;
}

Logger::log('CRON_ID: '. $jobb->getCronId());
Logger::log('DIMENSIONS: w' .var_export( $file_width, true ) .' X h'.var_export( $file_height, true ));
Logger::log('PIXEL_FORMAT: ' . var_export( $pixel_format, true ));
Logger::log('DURATION: ' . var_export( $file_duration, true ) );
Logger::log('SUCCESS!');

Logger::log('TRIGGER CONVERT');
Trigger::nextFirstConvert();
Logger::log('!! COMPLETE');