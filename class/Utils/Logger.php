<?php

namespace UKMNorge\Videoconverter\Utils;

use Exception;

class Logger {

    static $id = '';
    static $cron = '';

    public static function log( $message ): void {
        if( is_bool() ) {
            $message = 'BOOL:'. $message ? 'TRUE' : 'FALSE';
        } elseif( is_scalar($message)) {
            $message = (string) $message;
        } else {
            $message = var_export( $message, true);
        }
        error_log( static::$id . static::$cron . $message );
    }

    public static function setId( String $id ) : void {
        static::$id = $id.': ';
    }

    public static function setCron( Int $cron_id ): void {
        static::$cron = '(CRON:'. (string) $cron_id .') '; 
    }

    public static function setLocation( String $path ) : void {
        throw new Exception('LOGGER: Implementer setLocation');
    }
}