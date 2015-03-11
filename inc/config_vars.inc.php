<?php
$file_name_input            = $cron['file_name'];
$file_name_output_raw       = str_replace($cron['file_type'], '', $file_name_input);
$file_name_output_hd        = $file_name_output_raw . $file_extension_hd;
$file_name_output_mobile    = $file_name_output_raw . $file_extension_mobile;
$file_name_output_archive	= $file_name_output_raw . $file_extension_archive;
$file_name_output_image     = $file_name_output_raw . '.jpg';

// Full paths to ffmpeg files
$file_input             = DIR_TEMP_CONVERT . $file_name_input;

$file_output_hd         = DIR_TEMP_CONVERTED . $file_name_output_hd;
$file_output_mobile     = DIR_TEMP_CONVERTED . $file_name_output_mobile;
$file_output_archive	= DIR_TEMP_CONVERTED . $file_name_output_archive;

$file_store_hd          = DIR_TEMP_STORE . $file_name_output_hd;
$file_store_mobile      = DIR_TEMP_STORE . $file_name_output_mobile;
$file_store_archive		= DIR_TEMP_STORE . $file_name_output_archive;
$file_store_image       = DIR_TEMP_STORE . $file_name_output_raw.'.jpg';

$file_log_raw_hd        = DIR_TEMP_LOG . $file_name_output_raw . $file_id_hd;
$file_log_raw_mobile    = DIR_TEMP_LOG . $file_name_output_raw . $file_id_mobile;
$file_log_raw_archive	= DIR_TEMP_LOG . $file_name_output_raw . $file_id_archive;
$file_log_raw_image     = DIR_TEMP_LOG . $file_name_output_raw;

$file_log_fp_hd         = $file_log_raw_hd . '_firstpass.txt';
$file_log_fp_mobile     = $file_log_raw_mobile . '_firstpass.txt';
$file_log_fp_archive	= $file_log_raw_archive . '_firstpass.txt';
$file_log_sp_hd         = $file_log_raw_hd . '_secondpass.txt';
$file_log_sp_mobile     = $file_log_raw_mobile . '_secondpass.txt';
$file_log_sp_archive	= $file_log_raw_archive . '_secondpass.txt';
$file_log_image         = $file_log_raw_image . '_image.txt';
