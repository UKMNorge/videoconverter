<?php

define('DIR_BASE', str_replace('/inc','', dirname( __FILE__ )).'/');
define('DIR_TEMP', DIR_BASE .'temp_storage/');

define('DIR_TEMP_UPLOAD', DIR_TEMP .'uploaded/');
define('DIR_TEMP_CONVERT', DIR_TEMP .'convert/');		// File in conversion atm
define('DIR_TEMP_CONVERTED', DIR_TEMP .'converted/');	// The file after second pass
define('DIR_TEMP_FASTSTART', DIR_TEMP .'faststart/');	// The file after QT-FASTSTART
define('DIR_TEMP_STORE', DIR_TEMP .'store/');			// The file after QT-FASTSTART
define('DIR_TEMP_LOG', DIR_TEMP .'log/');
define('DIR_TEMP_x264', DIR_TEMP .'x264/');

// FIND FILENAME IN AND OUT
$file_id_hd					= '_720p';
$file_id_mobile				= '_mobile';
$file_extension_hd			= $file_id_hd . '.mp4';
$file_extension_mobile		= $file_id_mobile . '.mp4';

// FFMPEG-CONFIG (basics)
define('AUDIO_BITRATE_HD', 190);
define('AUDIO_SAMPLINGRATE_HD', 44100);
define('VIDEO_BITRATE_HD', 3000);

define('AUDIO_BITRATE_MOBILE', 128);
define('AUDIO_SAMPLINGRATE_MOBILE', 44100);
define('VIDEO_BITRATE_MOBILE', 890);

//define('REMOTE_SERVER', 'http://video.ukm.no');
#define('REMOTE_SERVER', 'http://video2.ukm.no');
define('REMOTE_SERVER', '10.0.1.181');

// DATABASE CONNECTION
$connect = mysql_connect('localhost', UKM_VIDEOCONVERTER_DB_USER, UKM_VIDEOCONVERTER_DB_PASS) or die(mysql_error());
mysql_select_db('converter', $connect) or die(mysql_error()); 
