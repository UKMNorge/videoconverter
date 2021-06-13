<?php

namespace UKMNorge\Videoconverter\Utils;

use UKMNorge\Videoconverter\Converter;

class Logger {

    const DIR_LOG = 'log/';

    static $id = '';
    static $cron = '';

    /**
     * Logg en melding
     *
     * @param any $message
     * @return String
     */
    public static function log( $message ): String {
        if( is_bool($message) ) {
            $message = 'BOOL:'. ($message ? 'TRUE' : 'FALSE');
        } elseif( is_scalar($message)) {
            $message = (string) $message;
        } else {
            $message = var_export( $message, true);
        }
        error_log( static::$id . static::$cron . $message );

        return $message;
    }

    /**
     * Logg en melding og varsle admin
     *
     * @param any $message
     * @return String
     */
    public static function notify( $message ): String {
        return static::log( '(SERVER ADMIN NOTIFICATION)'. $message );
    }

    /**
     * Sett en generisk ID for å gjenkjenne prosessen
     *
     * @param String $id
     * @return void
     */
    public static function setId( String $id ) : void {
        static::$id = $id.': ';
    }

    /**
     * Angi hvilken cron vi jobber med nå
     * 
     * Dette gir en egen log location
     *
     * @param Int $cron_id
     * @return void
     */
    public static function setCron( Int $cron_id ): void {
        static::$cron = 'CRON:'. (string) $cron_id .' '; 
        static::setLocation('cron_'. $cron_id);
    }

    /**
     * Angi hvor loggen skal lagres
     *
     * @param String $path
     * @return void
     */
    public static function setLocation( String $path ) : void { 
        ini_set('error_log', Converter::DIR_BASE . static::DIR_LOG . basename($path) . '.log');
    }
}