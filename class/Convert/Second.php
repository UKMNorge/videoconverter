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
        return "`status_progress` = 'archive'
		    AND (`status_archive` IS NULL OR `status_archive` = 'convert')";
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
            FROM `" . Converter::TABLE . "`
            WHERE `status_final_convert` != 'complete'
    		 AND `status_progress` = 'converting'
            LIMIT 1"
        );

        return !!$query->getField();
    }

    /**
     * @param Jobb $jobb 
     * @return void 
     */
    static function start( Jobb $jobb ) : void {
        parent::start($jobb);

        $query = new Update(Converter::TABLE, ['id' => $jobb->getId()]);
        $query->add('file_name_store', $jobb->getFil()->getNavnUtenExtension() .'.mp4');
        $query->run();
    }
}