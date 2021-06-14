<?php

namespace UKMNorge\Videoconverter\Versjon;


interface VersjonInterface {
    public function getFFmpegKall( String $preset ): String;
}