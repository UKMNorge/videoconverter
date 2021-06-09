<?php

namespace UKMNorge\Videoconverter\Convert;

use Exception;
use UKMNorge\Database\SQL\Update;
use UKMNorge\Videoconverter\Converter;
use UKMNorge\Videoconverter\Jobb;
use UKMNorge\Videoconverter\Versjon\HD;
use UKMNorge\Videoconverter\Versjon\Mobil;

class First extends Common {
    const PRESET = 'ultrafast';
    const PRESET_MOBILE = 'ultrafast';
    const DB_FIELD = 'status_first_convert';

    public static function getNextQueryWhere() : String {
        return "`status_progress` = 'registered'";
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

    /**
     * Hvilke versjoner vi skal gjøre i denne runden
     *
     * @return Array<Versjon>
     */
    public static function getVersjoner( Jobb $jobb ) : array {
        return [ new HD( $jobb, static::PRESET ), new Mobil( $jobb, static::PRESET_MOBILE ) ];
    }

}