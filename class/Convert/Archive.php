<?php

namespace UKMNorge\Videoconverter\Convert;

use UKMNorge\Videoconverter\Converter;
use UKMNorge\Videoconverter\Database\Query;
use UKMNorge\Videoconverter\Jobb;
use UKMNorge\Videoconverter\Versjon\Arkiv;
use UKMNorge\Videoconverter\Versjon\Bilde;
use UKMNorge\Videoconverter\Versjon\HD;
use UKMNorge\Videoconverter\Versjon\Metadata;
use UKMNorge\Videoconverter\Versjon\Mobil;

class Archive extends Common
{
    const PRESET = 'slower';
    const DB_FIELD = 'status_archive';

    /**
     * WHERE-parameter som brukes for å finne neste konverteringsjobb
     *
     * @return String
     */
    public static function getNextQueryWhere(): String
    {
        return "WHERE `status_progress` = 'archive'
        AND (`status_archive` IS NULL OR `status_archive` = 'convert')";
    }

    /**
     * Kjører det allerede en konverteringsjobb av denne typen?
     *
     * @return boolean
     */
    public static function isRunning(): bool
    {

        $query = new Query(
            "SELECT `id` FROM `ukmtv`
       		WHERE (`status_progress` = 'converting' AND `status_final_convert` = 'converting')
	    	OR (`status_progress` = 'converting' AND `status_archive` = 'converting')
            LIMIT 1"
        );

        return !!$query->getField();
    }

    /**
     * Hvilke versjoner vi skal gjøre i denne runden
     *
     * @return Array<Versjon>
     */
    public static function getVersjoner(Jobb $jobb): array
    {
        return [
            new Arkiv($jobb, static::PRESET),
            new Bilde($jobb),
            new Metadata($jobb)
        ];
    }

    /**
     * Fordi arkiveringen skal trigge en annen cron-jobb en store cron,
     * overskriver vi statusen her
     *
     * @param Jobb $jobb
     * @return void
     */
    public static function completed(Jobb $jobb): void
    {
        parent::completed($jobb);
        $jobb->saveStatus('archive');
    }

    /**
     * Slett også de konverterte HD og Mobil-utgaver når vi arkiverer
     *
     * @param Jobb $jobb
     * @return array<String>
     */
    public static function getFilesToDelete(Jobb $jobb): array
    {
        $hd = new HD($jobb, null);
        $mobil = new Mobil($jobb, null);
        
        return array_merge(
            parent::getFilesToDelete($jobb),
            [
                $hd->getOutputFilePath(),
                $mobil->getOutputFilePath()
            ]
        );
    }

    /**
     * Finnes det filmer som skal arkiveres
     * 
     * Topprioritet for videoconverteren er å tilgjengeliggjøre mest mulig innhold,
     * og alle andre får dermed vente til alle filmer er førstegangs-konvertert.
     *
     * @return boolean
     */
    public static function hasTodo(): bool
    {
        $query = new Query(
            "SELECT `id`
            FROM `" . Converter::TABLE . "`
            ". static::getNextQueryWhere() ."
            LIMIT 1"
        );

        return !!$query->getField();
    }
}
