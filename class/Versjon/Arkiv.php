<?php

namespace UKMNorge\Videoconverter\Versjon;

class Arkiv extends Versjon
{

    const VIDEO_BITRATE = 8000;
    const AUDIO_BITRATE = 360;
    const FILE_ID = '_archive';
    const HEIGHT = 1080;

    public function getFFmpegKall(): String
    {
        return
            ####### FIRST PASS #######
            'ffmpeg '
            . '-y '                                             # overskriv fil uten å spørre
            . '-i ' . $this->getInputFilePath() . ' '           # Input-fil
            . '-threads 0 '                                     # Antall tråder, 0 kan utnytte alle
            . '-movflags faststart '                            # Istedenfor qtfaststart
            . '-g 75 '                                          # Antall tråder, 0 kan utnytte alle
            . '-keyint_min 50 '                                 # Antall tråder, 0 kan utnytte alle

            ## VIDEO
            . '-bt ' . (static::VIDEO_BITRATE * 1.5) . 'k '     # +/- target bitrate
            . '-b:v ' . static::VIDEO_BITRATE . 'k '            # Target bitrate basert på UKM-tabell
            . '-c:v libx264 '                                   # Bruk videocodec libx264
            . '-preset ' . $this->getPreset() . ' '             # Mest detaljerte preset (placebo er ikke verdt forskjellen)
            . '-r 25 '                                          # Tvinger 25fps
            . '-pass 1 '                                        # Kjør first-pass
            . '-passlogfile ' . $this->getX264FilePath() . ' '  # definerer hvor libx264 statfilen skal lagres
            . '-an '                                            # Drop audio, spar tid
            . '-s ' . $this->getJobb()->getFilm()->getOpplosningForHoyde(static::HEIGHT) . ' '   # Videooppløsning
            . '-f mp4 /dev/null '                               # Output MP4-fil til ingenting da vi ikke skal bruke denne
            . '2> ' . $this->getFirstPassLogPath() . ' '        # Angi logfil for ffmpeg

            ####### SECOND PASS #######
            . '&& ffmpeg '                                      # Kjør 2-pass hvis 1-pass == success
            . '-y '                                             # overskriv fil uten å spørre
            . '-i ' . $this->getInputFilePath() . ' '           # Input-fil
            . '-threads 0 '                                     # Antall tråder, 0 kan utnytte alle
            . '-movflags faststart '                            # Istedenfor qtfaststart
            . '-g 75 '                                          # GOP-interval (keyframe interval)
            . '-keyint_min 50 '                                 # Minimum GOP interval

            ## VIDEO
            . '-bt ' . (static::VIDEO_BITRATE * 1.5) . 'k '     # +/- target bitrate
            . '-b:v ' . static::VIDEO_BITRATE . 'k '            # Target bitrate basert på UKM-tabell
            . '-c:v libx264 '                                   # Bruk videocodec libx264
            . '-preset ' . $this->getPreset() . ' '             # Mest detaljerte preset (placebo er ikke verdt forskjellen)
            . '-r 25 '                                          # Tvinger 25fps
            . '-pass 2 '                                        # Kjør second-pass
            . '-passlogfile ' . $this->getX264FilePath() . ' '  # definerer hvor libx264 statfilen skal leses

            ## AUDIO
            . '-c:a libfdk_aac '                                # Bruk videocodec libfdk_aac
            . '-cutoff 18000 '                                  # Cutoff-frekvens på lyd (default for libfdk_aac er 15kHz)
            . '-aq 100 '                                        # Audiokvalitet 100%
            . '-b:a ' . static::AUDIO_BITRATE . 'k '            # Audio bitrate fra config
            . '-ar ' . static::AUDIO_SAMPLINGRATE . ' '         # Audio sampling rate (Hz) fra config
            . '-s ' . $this->getJobb()->getFilm()->getOpplosningForHoyde(static::HEIGHT) . ' ' # Videooppløsning
            . '-f mp4 ' . $this->getOutputFilePath() . ' 2> '   # Output MP4-fil (tving dette..?)
            . $this->getSecondPassLogPath() . ' ';              # Angi logfil for ffmpeg

            ####### QT FASTSTART #######
            #. '&& qt-faststart '.$file_output_archive
            #. ' ' . $file_store_archive;            # Kjør QT Faststart og flytt til lagringsmappe (klar for henting)
    }
}
