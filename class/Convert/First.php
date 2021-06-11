<?php

namespace UKMNorge\Videoconverter\Convert;

use Exception;
use UKMNorge\Database\SQL\Update;
use UKMNorge\Videoconverter\Converter;
use UKMNorge\Videoconverter\Jobb;

class First extends Common
{
    const PRESET = 'ultrafast';
    const DB_FIELD = 'status_first_convert';

    /**
     * WHERE-parameter som brukes for å finne neste konverteringsjobb
     *
     * @return String
     */
    public static function getNextQueryWhere(): String
    {
        return "`status_progress` = 'registered'";
    }

    /**
     * Finnes det filmer som er registrert, men ikke førstegangs-konvertert?
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
            WHERE `status_progress` = 'registered'
            LIMIT 1"
        );

        return !!$query->getField();
    }

    /**
     * I tillegg til å logge start, er det FirstConvert sin jobb
     * å lagre filnavn som senere brukes av lagringsfunksjonene
     * 
     * @param Jobb $jobb 
     * @return void 
     */
    static function start(Jobb $jobb): void
    {
        parent::start($jobb);

        $query = new Update(Converter::TABLE, ['id' => $jobb->getId()]);
        $query->add('file_name_store', $jobb->getFil()->getNavnUtenExtension() . '.mp4');
        $query->run();
    }
}
