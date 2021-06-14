<?php

namespace UKMNorge\Videoconverter\Convert;

use UKMNorge\Videoconverter\Database\Query;
use UKMNorge\Videoconverter\Database\Update;
use UKMNorge\Videoconverter\Converter;
use UKMNorge\Videoconverter\Jobb;

class Second extends Common {
    const PRESET = 'slower';
    const DB_FIELD = 'status_final_convert';

    /**
     * WHERE-parameter som brukes for å finne neste konverteringsjobb
     *
     * @return String
     */
    public static function getNextQueryWhere() : String {
        return "
        WHERE `status_progress` = 'converting'
        AND `status_first_convert` = 'complete'
        AND (`status_final_convert` IS NULL OR `status_final_convert` = 'convert')";
    }

    /**
     * Finnes det filmer som skal andregangs-konverteres?
     * 
     * Topprioritet for videoconverteren er å tilgjengeliggjøre mest mulig innhold.
     * Akrivering skjer først når vi har andregangskonvertert alle filmer
     *
     * @return boolean
     */
    public static function hasTodo(): bool
    {
        $query = new Query(
            "SELECT `id`
            FROM `" . Converter::TABLE . "` "
            . static::getNextQueryWhere() ."
            LIMIT 1"
        );

        return !!$query->getField();
    }
}