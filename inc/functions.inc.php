<?php

class DB {
	public static $connection = null;

	/**
	 * Koble til databasen
	 *
	 * @return void
	 */
	public static function connect() {
		static::$connection = new mysqli(
			'localhost',
			UKM_VIDEOCONVERTER_DB_USER,
			UKM_VIDEOCONVERTER_DB_PASS,
			'converter'
		);
	}

	/**
	 * Kjør en databasespørring
	 *
	 * @param String $query
	 * @return any
	 */
	public static function query( String $query ) {
		if( is_null( static::$connection ) ) {
			static::connect();
		}
		return static::$connection->query( $query );
	}

	/**
	 * Hent siste insert id
	 *
	 * @return Int
	 */
	public static function getInsertId() : Int {
		return static::$connection->insert_id;
	}
}


function ukmtv_update($field, $status, $id) {	
	logg('DB: '. "UPDATE `ukmtv` SET `".$field."` = '".$status."' WHERE `id` = '".$id."'");
	
	DB::query("UPDATE `ukmtv` SET `".$field."` = '".$status."' WHERE `id` = '".$id."'");
}

function logg( $message ) {
    error_log(LOG_SCRIPT_NAME .' '. CRON_ID .': '. $message );
} 

function notify( $message ) {
	logg( $message );
	if( defined('CRON_ID') && is_numeric( CRON_ID ) ) {
		DB::query("UPDATE `ukmtv` SET `admin_notice` = 'true' WHERE `id` = '". CRON_ID ."'");
	}
    
    error_log('SERVER ADMIN NOTIFICATION: cid'. CRON_ID .': '. $message );
}
