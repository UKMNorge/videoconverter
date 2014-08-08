<?php

// ALWAYS FINISH EVEN IF USER ABORTS!!!
ignore_user_abort(true);

$timer_start = microtime(true);

if( !defined('CONVERT_PASS') )
	die('Choose a cron! (first / final)');

if( !isset($cron) )
	die('$cron is not set, got nothing to do..');

if( !$cron )
	die('Nothing to do! (empty $cron)');

require_once('../inc/config.inc.php');
require_once('../inc/functions.inc.php');


####################################################################################
## EXECUTE CRON CONVERT (JOB IS SELECTED)

	// UPDATE DATABASE - WE'RE NOW CONVERTING
	ukmtv_update($dbfield, 'converting', $cron['id']);
	ukmtv_update('status_progress', 'converting', $cron['id']);
		
	$file_name_input			= $cron['file_name'];
	$file_name_output_raw		= str_replace($cron['file_type'], '', $file_name_input);
	$file_name_output_hd		= $file_name_output_raw . $file_extension_hd;
	$file_name_output_mobile	= $file_name_output_raw . $file_extension_mobile;
	$file_name_output_image		= $file_name_output_raw . '.jpg';
	
	// Full paths to ffmpeg files
	$file_input				= DIR_TEMP_CONVERT . $file_name_input;	
	
	$file_output_hd			= DIR_TEMP_CONVERTED . $file_name_output_hd;
	$file_output_mobile		= DIR_TEMP_CONVERTED . $file_name_output_mobile;
	
	$file_store_hd			= DIR_TEMP_STORE . $file_name_output_hd;
	$file_store_mobile		= DIR_TEMP_STORE . $file_name_output_mobile;
	$file_store_image		= DIR_TEMP_STORE . $file_name_output_raw.'.jpg';
	
	$file_log_raw_hd		= DIR_TEMP_LOG . $file_name_output_raw . $file_id_hd;
	$file_log_raw_mobile	= DIR_TEMP_LOG . $file_name_output_raw . $file_id_mobile;
	$file_log_raw_image		= DIR_TEMP_LOG . $file_name_output_raw;
	
	$file_log_fp_hd			= $file_log_raw_hd . '_firstpass.txt';
	$file_log_fp_mobile		= $file_log_raw_mobile . '_firstpass.txt';
	$file_log_sp_hd			= $file_log_raw_hd . '_secondpass.txt';
	$file_log_sp_mobile	 	= $file_log_raw_mobile . '_secondpass.txt';
	$file_log_image		 	= $file_log_raw_image . '_image.txt';
	
	
	$file_x264				= DIR_TEMP_x264 . $file_name_output_raw .'_x264data.txt';
	
	// VIDEO ASPECT RATIO
	$video_width_raw		= $cron['file_width'];
	$video_height_raw		= $cron['file_height'];
	$video_ratio			= $video_width_raw / $video_height_raw;
	$video_width_hd			= round( $video_ratio * 720 );
	$video_width_mobile		= round( $video_ratio * 480 );
	
	if( $video_width_hd % 2 != 0 )
		$video_width_hd--;
	if( $video_width_mobile % 2 != 0 )
		$video_width_mobile--;
	
	$video_resolution_hd	 	= $video_width_hd .'x720';
	$video_resolution_mobile 	= $video_width_mobile .'x480';

	if(CONVERT_PASS == 'first')
		ukmtv_update('file_name_store', $file_name_output_raw . '.mp4', $cron['id']);
		
	$call_hd = 
		####### FIRST PASS #######
		'ffmpeg '
		. '-y '									# overskriv fil uten å spørre
		. '-i '.$file_input.' '					# Input-fil
		. '-threads 0 ' 						# Antall tråder, 0 kan utnytte alle
		. '-g 75 '		 						# Antall tråder, 0 kan utnytte alle
		. '-keyint_min 50 '			 			# Antall tråder, 0 kan utnytte alle
	
		## VIDEO
		. '-bt '.(VIDEO_BITRATE_HD*1.5).'k '	# +/- target bitrate
		. '-b:v '.VIDEO_BITRATE_HD.'k '			# Target bitrate basert på UKM-tabell
		. '-c:v libx264 '						# Bruk videocodec libx264
		. '-preset ' .$preset .' '				# Mest detaljerte preset (placebo er ikke verdt forskjellen)
		. '-r 25 '								# Tvinger 25fps
		. '-pass 1 '							# Kjør first-pass
		. '-passlogfile '.$file_x264.' '		# definerer hvor libx264 statfilen skal lagres
		. '-an '								# Drop audio, spar tid
		. '-s '. $video_resolution_hd.' '		# Videooppløsning
		. '-f mp4 /dev/null '					# Output MP4-fil til ingenting da vi ikke skal bruke denne
		. '2> '. $file_log_fp_hd.' '			# Angi logfil for ffmpeg
		
		####### SECOND PASS #######
		.'&& ffmpeg '							# Kjør 2-pass hvis 1-pass == success
		. '-y '									# overskriv fil uten å spørre
		. '-i '. $file_input.' '			 	# Input-fil
		. '-threads 0 ' 						# Antall tråder, 0 kan utnytte alle
		. '-g 75 '		 						# GOP-interval (keyframe interval)
		. '-keyint_min 50 '			 			# Minimum GOP interval
		
		## VIDEO
		. '-bt '.(VIDEO_BITRATE_HD*1.5).'k '	# +/- target bitrate
		. '-b:v '.VIDEO_BITRATE_HD.'k '			# Target bitrate basert på UKM-tabell
		. '-c:v libx264 '						# Bruk videocodec libx264
		. '-preset '.$preset.' '				# Mest detaljerte preset (placebo er ikke verdt forskjellen)
		. '-r 25 '								# Tvinger 25fps
		. '-pass 2 '							# Kjør second-pass
		. '-passlogfile '.$file_x264.' '		# definerer hvor libx264 statfilen skal leses
	
		## AUDIO
		. '-c:a libfdk_aac '						# Bruk videocodec libfdk_aac
		. '-aq 100 '							# Audiokvalitet 100%
		. '-ab '.AUDIO_BITRATE_HD.'k '			# Audio bitrate fra config
		. '-ar '.AUDIO_SAMPLINGRATE_HD.' '		# Audio sampling rate (Hz) fra config
		. '-s '. $video_resolution_hd.' '		# Videooppløsning
		. '-f mp4 '. $file_output_hd .' 2> '	# Output MP4-fil (tving dette..?)
			. $file_log_sp_hd.' '				# Angi logfil for ffmpeg
	
		####### QT FASTSTART #######
		. '&& qt-faststart '.$file_output_hd
		. ' ' . $file_store_hd;					# Kjør QT Faststart og flytt til lagringsmappe (klar for henting)
	
	
	$call_mobile = 
		####### FIRST PASS #######
		'ffmpeg '
		. '-y '									# overskriv fil uten å spørre
		. '-i '.$file_input.' '					# Input-fil
		. '-threads 0 ' 						# Antall tråder, 0 kan utnytte alle
		. '-g 75 '		 						# Antall tråder, 0 kan utnytte alle
		. '-keyint_min 50 '			 			# Antall tråder, 0 kan utnytte alle
	
		## VIDEO
		. '-bt '.(VIDEO_BITRATE_MOBILE*1.5).'k '# +/- target bitrate
		. '-b:v '.VIDEO_BITRATE_MOBILE.'k '		# Target bitrate basert på UKM-tabell
		. '-c:v libx264 '						# Bruk videocodec libx264
		. '-preset ' .$preset_mobile .' '				# Mest detaljerte preset (placebo er ikke verdt forskjellen)
		. '-profile:v baseline -level 3.1 '		# Alle fleste telefoner (http://en.wikipedia.org/wiki/H.264#Levels)
		. '-r 25 '								# Tvinger 25fps
		. '-pass 1 '							# Kjør first-pass
		. '-passlogfile '.$file_x264.' '		# definerer hvor libx264 statfilen skal lagres
		. '-an '								# Drop audio, spar tid
		. '-s '. $video_resolution_mobile.' '	# Videooppløsning
		. '-f mp4 /dev/null '					# Output MP4-fil til ingenting da vi ikke skal bruke denne
		. '2> '. $file_log_fp_mobile.' '		# Angi logfil for ffmpeg
		
		####### SECOND PASS #######
		.'&& ffmpeg '							# Kjør 2-pass hvis 1-pass == success
		. '-y '									# overskriv fil uten å spørre
		. '-i '. $file_input.' '			 	# Input-fil
		. '-threads 0 ' 						# Antall tråder, 0 kan utnytte alle
		. '-g 75 '		 						# GOP-interval (keyframe interval)
		. '-keyint_min 50 '			 			# Minimum GOP interval
		
		## VIDEO
		. '-bt '.(VIDEO_BITRATE_MOBILE*1.5).'k '# +/- target bitrate
		. '-b:v '.VIDEO_BITRATE_MOBILE.'k '		# Target bitrate basert på UKM-tabell
		. '-c:v libx264 '						# Bruk videocodec libx264
		. '-preset '.$preset_mobile.' '				# Mest detaljerte preset (placebo er ikke verdt forskjellen)
		. '-profile:v baseline -level 3.1 '		# Alle fleste telefoner (http://en.wikipedia.org/wiki/H.264#Levels)
		. '-r 25 '								# Tvinger 25fps
		. '-pass 2 '							# Kjør second-pass
		. '-passlogfile '.$file_x264.' '		# definerer hvor libx264 statfilen skal leses
	
		## AUDIO
		. '-c:a libfdk_aac '						# Bruk videocodec libfdk_aac
		. '-aq 100 '							# Audiokvalitet 100%
		. '-ab '.AUDIO_BITRATE_MOBILE.'k '		# Audio bitrate fra config
		. '-ar '.AUDIO_SAMPLINGRATE_MOBILE.' '	# Audio sampling rate (Hz) fra config
		. '-s '. $video_resolution_mobile.' '	# Videooppløsning
		. '-f mp4 '. $file_output_mobile .' 2> '# Output MP4-fil (tving dette..?)
			. $file_log_sp_mobile.' '			# Angi logfil for ffmpeg
	
		####### QT FASTSTART #######
		. '&& qt-faststart '.$file_output_mobile
		. ' ' . $file_store_mobile;				# Kjør QT Faststart og flytt til lagringsmappe (klar for henting)
		
	$call_image = 
		'ffmpeg '
		. '-y '									# overskriv fil uten å spørre
		. '-i '.$file_input.' '					# Input-fil
		. '-an '								# Drop audio, spar tid
		. '-ss 00:00:08 '						# @ second 08
		. '-r 1 '								# Framerate 1
		. '-vframes 1 '							# DUNNO
		. '-s hd720 '							# Size of image
		. '-f image2 '							# DUNNO
		. '-vcodec mjpeg '						# Video-codec
		. '-qscale 1 '							# DUNNO
		. $file_store_image . ' 2> '			# Output-fil
		. $file_log_image						# Logg-fil
		;
		
        
    define('CRON_ID', $cron['id']);
    define('LOG_SCRIPT_NAME', 'CONVERT.INC.PHP');
    logg('START');
    logg('CONVERT PASS: '. CONVERT_PASS);
    
    $ERROR = false;
    
    $create = array('hd' => 'HD', 'mobile' => 'MOB', 'image' => 'IMG');
    foreach( $create as $varname => $name ) {
        if( $ERROR ) {
            break;
        }
        
        logg('CONVERT '. $name);
        $timer_start = microtime(true);
        exec(${'call_'.$varname}, ${'response_'.$varname});
        logg('CONVERT '. $name .' RESPONSE: '. var_export( ${'response_'.$varname}, true));
        $timer_stop = microtime(true);
        logg('CONVERT '. $name .' TIME: '. ($timer_stop-$timer_start));
        
        if( ${'response_'.$varname} != null ) {
            logg('FAILED! ERROR discovered, set status = "crashed" and move on');
            $ERROR = true;
        }
    }

	if( $ERROR ) {
    	ukmtv_update('status_progress', 'crashed', $cron['id']);
    	logg('FAILURE! END OF CRON.');
	} else {
	    logg('CONVERT COMPLETE');
    	// UPDATE DATABASE - WE'RE NOW CONVERTED
    	ukmtv_update($dbfield, 'complete', $cron['id']);
    	ukmtv_update('status_progress', 'store', $cron['id']);
    	
    /*
    	if(CONVERT_PASS == 'final') {
    		ukmtv_update('status_progress', 'converted', $cron['id']);
    	}
    */
    	
    	// Slett converted-filene ( bevarer convert + store-filene)
    	// Store vil slette de to siste + logger
    	unlink($file_output_hd);
    	unlink($file_output_mobile);
    	unlink($file_x264.'-0.log');
    	unlink($file_x264.'-0.log.mbtree');
    	
    	// Trigger transfer of file to storage server
    	echo '<h1>Triggering start of store-cron</h1>';
    	echo 'Cron will self make sure only one file is transferred';
    	
    	require_once('../inc/curl.class.php');
    	$store = new UKMCURL();
    	$store->timeout(2);
    	$store->request('http://videoconverter. ' . UKM_HOSTNAME . '/cron/store.cron.php');
    }
