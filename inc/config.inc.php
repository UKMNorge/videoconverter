<?php


define('DIR_BASE', str_replace('/inc', '', dirname(__FILE__)) . '/');
define('DIR_TEMP', DIR_BASE . 'temp_storage/');
#define('DIR_LOG', DIR_BASE . 'log/');

#define('DIR_TEMP_UPLOAD', DIR_TEMP . 'uploaded/');
#define('DIR_TEMP_CONVERT', DIR_TEMP . 'convert/');        // File in conversion atm
#define('DIR_TEMP_CONVERTED', DIR_TEMP . 'converted/');    // The file after second pass
#define('DIR_TEMP_FASTSTART', DIR_TEMP . 'faststart/');    // The file after QT-FASTSTART
#define('DIR_TEMP_STORE', DIR_TEMP . 'store/');            // The file after QT-FASTSTART
define('DIR_TEMP_LOG', DIR_TEMP . 'log/');
#define('DIR_TEMP_x264', DIR_TEMP . 'x264/');
define('DIR_FINAL_ARCHIVE', DIR_BASE . 'MNT_archive_at_digark/UKMTV/');

// FIND FILENAME IN AND OUT
#$file_id_hd                    = '_720p';
#$file_id_mobile                = '_mobile';
#$file_id_archive            = '_archive';
#$file_extension_hd            = $file_id_hd . '.mp4';
#$file_extension_mobile        = $file_id_mobile . '.mp4';
#$file_extension_archive        = $file_id_archive . '.mp4';

// FFMPEG-CONFIG (basics)
#define('AUDIO_BITRATE_HD', 190);
#define('AUDIO_SAMPLINGRATE_HD', 44100);
#define('VIDEO_BITRATE_HD', 3000);

#define('AUDIO_BITRATE_MOBILE', 128);
#define('AUDIO_SAMPLINGRATE_MOBILE', 44100);
#define('VIDEO_BITRATE_MOBILE', 890);

#define('AUDIO_BITRATE_ARCHIVE', 360);
#define('AUDIO_SAMPLINGRATE_ARCHIVE', 44100);
#define('VIDEO_BITRATE_ARCHIVE', 8000);

define('REMOTE_SERVER', 'https://video.' . UKM_HOSTNAME);
