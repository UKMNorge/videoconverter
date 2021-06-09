<?php

namespace UKMNorge\Videoconverter\Convert;

use Exception;
use UKMNorge\Database\SQL\Query;
use UKMNorge\Database\SQL\Update;
use UKMNorge\Videoconverter\Jobb;
use UKMNorge\Videoconverter\Converter;
use UKMNorge\Videoconverter\Trigger;
use UKMNorge\Videoconverter\Utils\Logger;
use UKMNorge\Videoconverter\Utils\Timer;

abstract class Common implements ConvertInterface
{
    private $timer;

    /**
     * Kjører det allerede en konverteringsjobb av denne typen?
     *
     * @return boolean
     */
    public static function isRunning(): bool
    {

        $query = new Query(
            "SELECT `id` FROM `ukmtv`
		    WHERE `status_progress` = 'converting'
		    AND `" . static::DB_FIELD . "` = 'converting'
            LIMIT 1"
        );

        return !!$query->getField();
    }

    /**
     * Start konvertering 
     *
     * @return void
     */
    public static function startNext()
    {
        # Henter jobben
        $jobb = static::getNext();

        # Setter opp loggeren
        Logger::setId('Convert::' . basename(get_called_class()));
        Logger::setCron($jobb->getId());

        # Starter timeren
        static::$timer = new Timer('CONVERT CRON: ' . $jobb->getId());

        # Logger at vi starter
        static::start($jobb);

        # Sjekk at vi vet videostørrelsen
        try {
            static::sjekkDimensjoner($jobb);
        } catch (Exception $e ) {
            $jobb->saveStatus('crashed');
            throw $e;
        }

        # Kjør ffmpeg på de ulike utgavene
        foreach( static::getVersjoner( $jobb ) as $versjon ) {
            $timer_versjon = new Timer( $versjon::class );
            static::ffmpeg( $jobb, $versjon );
            Logger::log( $timer_versjon->__toString() );
        }

        static::cleanup();
        static::completed( $jobb );

        Trigger::store();

        return true;
    }

    public static function ffmpeg( Jobb $jobb, $versjon ) {
        $kommando = $versjon->getFFmpegKall();

        Logger::log('FFMPEG: '. $kommando);
        $return_code = null;
        $respons = null;
        exec($kommando, $respons, $return_code);
        Logger::log('RETURN CODE: '. $return_code );
        Logger::log('RESPONSE: '. var_export( $respons, true));
        
        if( $return_code != 0 ) {
            Logger::notify('FAILED! ERROR discovered, set status = "crashed" and move on');
            $jobb->saveStatus('crashed');
            Logger::logg('FAILURE! END OF CRON');
            throw new Exception(
                'Konvertering feilet '. $versjon::class .' ('. $jobb->getId() .')'
            );
        }
    }

    public static function completed( Jobb $jobb ) : void {
        
        $query = new Update(Converter::TABLE, ['id' => $jobb->getId()]);
        $query->add(static::DB_FIELD, 'complete');
        $query->add('status_progress', 'store');
        $query->run();
        Logger::log('SUCCESS! END OF CRON');
    }

    /**
     * Hent neste konverteringsjobb
     *
     * @return Jobb
     */
    public static function getNext(): Jobb
    {
        $query = new Query(
            "SELECT `id` FROM `" . Converter::TABLE . "`
            " . static::getNextQueryWhere() . "
            ORDER BY `id` ASC
            LIMIT 1"
        );

        $cron_id = $query->getField();

        if (!$cron_id) {
            throw new Exception(
                'Fant ikke ny konverteringsjobb for ' . get_called_class()
            );
        }

        return new Jobb($cron_id);
    }

    /**
     * Logg at vi starter med jobben
     *
     * @param Jobb $jobb
     * @return void
     */
    protected static function start(Jobb $jobb): void
    {
        $query = new Update(Converter::TABLE, ['id' => $jobb->getId()]);
        $query->add(static::DB_FIELD, 'converting');
        $query->add('status_progress', 'converting');
        $query->run();
    }

    /**
     * Sjekk at vi vet hvor stor filmen er
     *
     * @param Jobb $jobb
     * @throws Exception
     * @return bool
     */
    protected static function sjekkDimensjoner(Jobb $jobb): bool
    {
        if (empty($jobb->getFilm()->getBredde()) || empty($jobb->getFilm()->getHoyde())) {
            throw new Exception(
                Logger::notify('Mangler videostørrelser, og konvertering har derfor stoppet');
            );
        }
        return true;
    }
}
