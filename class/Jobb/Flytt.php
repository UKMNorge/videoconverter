<?php

namespace UKMNorge\Videoconverter\Jobb;

use UKMNorge\Videoconverter\Converter;

class Flytt
{
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
     * @return String
     */
    public function tilConvert(): String
    {
        return $this->move($this->fil->getFil(), Converter::DIR_TEMP . static::CONVERT);
    }

    /**
     * Flytt til temp_storage/convert
     *
     * @return String
     */
    public function tilConverted(): String
    {
        return $this->move($this->fil->getFil(), Converter::DIR_TEMP . static::CONVERTED);
    }

    /**
     * Flytt til temp_storage/store
     *
     * @return String
     */
    public function tilStorage(): String
    {
        return $this->move($this->fil->getFil(), Converter::DIR_TEMP . static::STORE);
    }


    /**
     * Flytt denne filen til $target
     *
     * @param $target
     * @return void
     */
    private function move(String $origin, String $destination): String
    {
        $origin .= $this->fil->getNavn();
        rename($origin, $destination);
        return $destination;
    }
}
