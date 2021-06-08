<?php

namespace UKMNorge\Videoconverter;

use UKMNorge\Http\Curl;

class Trigger
{
    /**
     * Start førstegangskonvertering
     *
     * @return boolean
     */
    public static function nextFirstConvert(): bool
    {
        return static::request('convert_first');
    }

    /**
     * Send trigger request
     *
     * @param String $endpoint
     * @return boolean
     */
    private static function request(String $endpoint): bool
    {
        $curl = new Curl();
        $curl->timeout(2);
        $curl->request(
            'https://videoconverter.' . UKM_HOSTNAME . '/cron/' . basename($endpoint) . '.cron.php'
        );

        return true;
    }
}
