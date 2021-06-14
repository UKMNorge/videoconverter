<?php

namespace UKMNorge\Videoconverter\Jobb;

use UKMNorge\Videoconverter\Converter;

class Flytt
{
    const INBOX = 'inbox/';
    const UPLOAD = 'uploaded/';
    const CONVERT = 'convert/';
    const CONVERTED = 'converted/';
    const FASTSTART = 'faststart/';
    const STORE = 'store/';
    const x264 = 'x264/';


    private $fil;

    public function __construct(Fil $fil)
    {
        $this->fil = $fil;
    }

    /**
     * Flytt til temp_storage/convert
     *
     * @param String $current_path
     * @return String
     */
    public function tilConvert( String $current_path ): String
    {
        return $this->move($current_path, Converter::DIR_TEMP . static::CONVERT);
    }

    /**
     * Flytt til temp_storage/convert
     *
     * @return String
     */
    public function tilConverted( String $current_path ): String
    {
        return $this->move($current_path, Converter::DIR_TEMP . static::CONVERTED);
    }

    /**
     * Flytt til temp_storage/store
     *
     * @return String
     */
    public function tilStorage( String $current_path ): String
    {
        return $this->move($current_path, Converter::DIR_TEMP . static::STORE);
    }


    /**
     * Flytt denne filen til $target
     *
     * @param $target
     * @return void
     */
    private function move(String $origin, String $destination): String
    {
        $destination .= $this->fil->getNavn();
        copy($origin, $destination);
        return $destination;
    }
}
