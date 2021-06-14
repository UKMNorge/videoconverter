<?php

namespace UKMNorge\Videoconverter\Convert;

use Exception;
use UKMNorge\Videoconverter\Database\Query;
use UKMNorge\Videoconverter\Converter;

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
        return "WHERE `status_progress` = 'registered'";
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
}
