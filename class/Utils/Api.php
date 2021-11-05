<?php

namespace UKMNorge\Videoconverter\Utils;

use UKMNorge\Http\Curl;
use UKMNorge\Videoconverter\Jobb;

class API
{
    static $cache = [];

    /**
     * Hent info om en film fra UKM.no-serveren
     *
     * @param Jobb $jobb
     * @return any
     */
    public static function getVideoInfo(Jobb $jobb)
    {
        return static::request('/video:info/'. $jobb->getId() .'/', $jobb);
    }

    /**
     * Hent en request fra runtime-cache eller API
     *
     * @param String $url
     * @param Jobb $jobb
     * @return any
     */
    private static function request(String $url, Jobb $jobb)
    {
        $url = 'https://api.' . UKM_HOSTNAME . $url;

        if (isset(static::$cache[$url])) {
            return static::$cache[$url];
        }

        $api = new Curl();
        $api->timeout(10);
        $result = $api->request($url, $jobb->getId());
        
        Logger::log('API REQUEST '. $url);
        Logger::log($result);

        static::$cache[$url] = $result;

        return $result;
    }
}
