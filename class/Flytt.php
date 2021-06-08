<?php

namespace UKMNorge\Videoconverter;

class Flytt
{

    const BASE = '/var/www/videoconverter/';
    const TEMP = static::BASE . 'temp_storage/';

    const UPLOAD = static::TEMP . 'uploaded/';
    const CONVERT = static::TEMP . 'convert/';
    const CONVERTED = static::TEMP . 'converted/';
    const FASTSTART = static::TEMP . 'faststart/';
    const STORE = static::TEMP . 'store/';
    const x264 = static::TEMP . 'x264/';


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
        return $this->move($this->fil->getFil(), static::CONVERT);
    }

    /**
     * Flytt til temp_storage/convert
     *
     * @return String
     */
    public function tilConverted(): String
    {
        return $this->move($this->fil->getFil(), static::CONVERTED);
    }

    /**
     * Flytt til temp_storage/store
     *
     * @return String
     */
    public function tilStorage(): String
    {
        return $this->move($this->fil->getFil(), static::STORE);
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
