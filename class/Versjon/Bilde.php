<?php

namespace UKMNorge\Videoconverter\Versjon;

class Bilde extends Versjon
{

    const FILE_ID = '';
    const EXT = '.jpg';
    const HEIGHT = 720; // og derav oppløsningen

    public function getFFmpegKall(String $preset = null): String
    {
        return
            'ffmpeg '
            . '-y '                                             # overskriv fil uten å spørre
            . '-i ' . $this->getInputFilePath() . ' '            # Input-fil
            . '-an '                                            # Drop audio, spar tid
            . '-ss ' . gmdate("H:i:s", $this->getJobb()->getFilm()->getBildePosisjon()) . ' '    # @ second 08
            . '-r 1 '                                           # Framerate 1
            . '-vframes 1 '                                     # DUNNO
            . '-s hd720 '                                       # Size of image
            . '-f image2 '                                      # DUNNO
            . '-vcodec mjpeg '                                  # Video-codec
            . '-q:v 1 '                                         # Bruk VBR
            . $this->getOutputFilePath() . ' 2> '               # Output-fil
            . $this->getImageLogPath()                          # Logg-fil
        ;
    }
}
