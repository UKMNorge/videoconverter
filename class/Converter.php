<?php

namespace UKMNorge\Videoconverter;

use UKMNorge\Videoconverter\Versjon\Bilde;
use UKMNorge\Videoconverter\Versjon\HD;
use UKMNorge\Videoconverter\Versjon\Mobil;
use UKMNorge\Videoconverter\Versjon\Versjon;

class Converter
{

    const TABLE = 'ukmtv';

    const REMOTE_SERVER = 'https://video.'. UKM_HOSTNAME;

    const DIR_BASE = '/var/www/videoconverter/';
    const DIR_TEMP = self::DIR_BASE . 'temp_storage/';

    /**
     * Hent endpoint for mottak av filer til lagringsserver
     *
     * @return String
     */
    public static function getStorageServerEndpoint(): String
    {
        return static::REMOTE_SERVER . '/receive.php';
    }

    /**
     * Hent endpoint hvor vi registrerer filmer på webservere
     *
     * @param Int $cron_id
     * @return String
     */
    public static function getVideoRegistrationEndpoint(Int $cron_id): String
    {
        return 'https://api.' . UKM_HOSTNAME . '/video:registrer/' . $cron_id;
    }

    /**
     * Hvilke versjoner støtter converteren?
     *
     * @param Jobb $jobb
     * @return Array<Versjon>
     */
    public static function getVersjoner(Jobb $jobb): array
    {
        return [
            new HD($jobb),
            new Mobil($jobb),
            new Bilde($jobb)
        ];
    }

    /**
     * Hvilke "typer" filmer er støttet for opplasting
     *
     * @return array<string>
     */
    public static function getSupportedVideoTypes() : array {
        return [
            'reportasje',
            'innslag'
        ];
    }
}
