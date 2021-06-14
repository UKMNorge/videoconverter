<?php

use UKMNorge\Videoconverter\Converter;
use UKMNorge\Videoconverter\Jobb\Eier;
use UKMNorge\Videoconverter\Jobb\Film;
use UKMNorge\Videoconverter\Jobb;
use UKMNorge\Videoconverter\Jobb\Flytt;
use UKMNorge\Videoconverter\Trigger;
use UKMNorge\Videoconverter\Utils\Logger;

require_once('inc/autoloader.php');

header('Content-Type: application/json; charset=utf-8');

Logger::setId('REGISTRER');
Logger::log('Registrer ny konverteringsjobb');

$data = new stdClass();

try {
    /**********************************************
     * VALIDER INPUT DATA
     **********************************************/

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception(
            Logger::log(
                'Invalid request type'
            )
        );
    }

    # Sjekk at alle POST-verdier er sendt med
    $post_values = ['blog_id', 'pl_id', 'season', 'b_id', 'type', 'file'];
    foreach ($post_values as $post_value) {
        if (!isset($_POST[$post_value])) {
            throw new Exception(
                Logger::log(
                    'Missing POST value "' . $post_value . '"'
                )
            );
        }
    }

    # Sjekk at vi har godkjent type film
    if (!in_array($_POST['type'], Converter::getSupportedVideoTypes())) {
        throw new Exception(
            Logger::log(
                'Invalid type of video. Supported types are ' .
                    '"' . join('", "', Converter::getSupportedVideoTypes()) . '"'
            )
        );
    }

    # Innslag-filmer MÅ ha numerisk Innslag-ID
    if ($_POST['type'] == 'innslag' && empty($_POST['b_id'])) {
        throw new Exception(
            Logger::log(
                'Innslag type videos require numeric b_id to be sent along as well. ' .
                    'Given ' . var_export($_POST['b_id'], true)
            )
        );
    }

    # Filnavn skal være basename for filen i inbox-mappa
    if (basename($_POST['file']) != $_POST['file']) {
        throw new Exception(
            Logger::log(
                'File name cannot contain path, and should be basename of the file.'
            )
        );
    }


    /**********************************************
     * REGISTRER FILMEN
     **********************************************/

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
            Converter::DIR_TEMP . Flytt::INBOX . basename($_POST['file'])
        );
    } catch (Exception $e) {
        Logger::log('EXCEPTION: ' . $e->getMessage());
        throw $e;
    }

    /**********************************************
     * LOGG DATA (for moro skyld)
     **********************************************/
    Logger::setCron($jobb->getId());
    Logger::log('CRON_ID: ' . $jobb->getId());
    Logger::log('DIMENSIONS: w' . $jobb->getFilm()->getBredde() . ' X h' . $jobb->getFilm()->getHoyde());
    Logger::log('PIXEL_FORMAT: ' . $jobb->getFilm()->getPikselFormat());
    Logger::log('DURATION: ' . $jobb->getFilm()->getVarighet().'s');
    Logger::log('SUCCESS!');

    /**********************************************
     * START CONVERTERING
     * Dette skjer også hvert minutt, men her kan
     * vi potensielt spare 59 sekunder 🤯🚀 
     **********************************************/

    Logger::log('TRIGGER CONVERT');
    Trigger::nextFirstConvert();
    Logger::log('!! COMPLETE');
} catch ( Exception $e ) {
    $data->success = false;
    $data->message = $e->getMessage();
    $data->code = $e->getCode();
    die(json_encode($data));
}

$data->success = true;
$data->cron_id = $jobb->getId();

die(json_encode($data));