<?php

namespace UKMNorge\Videoconverter\Convert;

use Exception;
use UKMNorge\Videoconverter\Database\Query;
use UKMNorge\Videoconverter\Database\Update;
use UKMNorge\Videoconverter\Jobb;
use UKMNorge\Videoconverter\Converter;
use UKMNorge\Videoconverter\Trigger;
use UKMNorge\Videoconverter\Utils\Logger;
use UKMNorge\Videoconverter\Utils\Timer;
use UKMNorge\Videoconverter\Versjon\Bilde;
use UKMNorge\Videoconverter\Versjon\HD;
use UKMNorge\Videoconverter\Versjon\Mobil;
use UKMNorge\Videoconverter\Versjon\Versjon;

abstract class Common implements ConvertInterface
{
    protected static $timer;
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
            LIMIT 1",
            [],
            'videoconverter'
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
        Logger::setId('Convert::' . substr(get_called_class(), strrpos(get_called_class(), '\\')));
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
            $timer_versjon = new Timer( get_class($versjon) );
            static::ffmpeg( $jobb, $versjon );
            Logger::log( $timer_versjon->__toString() );
        }
        Logger::log('Alle versjoner konvertert');

        # Slett midlertidig-filer (x264)
        static::cleanup( $jobb );

        # Oppdater databasen med ferdig
        static::completed( $jobb );

        # Prøv å starte lagringen
        Trigger::store();

        return true;
    }

    /**
     * Slett filer som bør fjernes
     *
     * @param Jobb $jobb
     * @return void
     */
    public static function cleanup( Jobb $jobb ) {
        foreach( static::getFilesToDelete( $jobb ) as $fil ) {
            if( file_exists( $fil ) ) {
                unlink($fil);
            }
        }
    }

    /**
     * Finn filer som skal slettes
     *
     * @param Jobb $jobb
     * @return array<String>
     */
    public static function getFilesToDelete( Jobb $jobb ): array {
        $versjon = new HD( $jobb );

        return [
            $versjon->getX264FilePath(),
            $versjon->getX264FilePath().'-0.log',
            $versjon->getX264FilePath().'-0.log.mbtree'
        ];
    }

    /**
     * Gjør ffmpeg-konverteringer
     *
     * @param Jobb $jobb
     * @param [type] $versjon
     * @return void
     */
    public static function ffmpeg( Jobb $jobb, $versjon ) {
        $kommando = $versjon->getFFmpegKall( static::PRESET );

        Logger::log('FFMPEG: '. $kommando);
        $return_code = null;
        $respons = null;
        exec($kommando, $respons, $return_code);
        Logger::log('RETURN CODE: '. $return_code );
        Logger::log('RESPONSE: '. var_export( $respons, true));
        
        if( $return_code != 0 ) {
            Logger::notify('FAILED! ERROR discovered, set status = "crashed" and move on');
            $jobb->saveStatus('crashed');
            
            throw new Exception(
                Logger::log(
                    'Konvertering feilet '. get_class($versjon) .' ('. $jobb->getId() .')'
                )
            );
        }
    }

    /**
     * Oppdater databasen med at jobben er gjort
     *
     * @param Jobb $jobb
     * @return void
     */
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
            LIMIT 1",
            [],
            'videoconverter'
        );
        $cron_id = $query->getField();

        echo $query->debug();

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
                Logger::notify('Mangler videostørrelser, og konvertering har derfor stoppet')
            );
        }
        return true;
    }

    /**
     * Hvilke versjoner vi skal gjøre i denne runden
     *
     * @return Array<Versjon>
     */
    public static function getVersjoner( Jobb $jobb ) : array {
        return [ 
            new HD( $jobb ),
            new Mobil( $jobb ),
            new Bilde( $jobb )
        ];
    }
}
