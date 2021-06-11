<?php

namespace UKMNorge\Videoconverter\Versjon;

use UKMNorge\Videoconverter\Converter;
use UKMNorge\Videoconverter\Jobb\Flytt;
use UKMNorge\Videoconverter\Utils\Logger;
use UKMNorge\Videoconverter\Jobb;

abstract class Versjon implements VersjonInterface
{

    const AUDIO_SAMPLINGRATE = 44100;
    const EXT = '.mp4';

    private $jobb;

    /**
     * Opprett et nytt versjonsobjekt
     *
     * @param Jobb $jobb
     */
    public function __construct(Jobb $jobb)
    {
        $this->jobb = $jobb;
    }

    /**
     * Hent filnavnet konvertert fil skal lagres som
     *
     * @return String
     */
    public function getFilnavn(): String
    {
        return $this->getJobb()->getFil()->getNavnUtenExtension() . static::getSuffix();
    }

    /**
     * Hent jobben som er aktiv
     *
     * @return Jobb
     */
    public function getJobb(): Jobb
    {
        return $this->jobb;
    }

    /**
     * Hent full filbane til filen som skal konverteres
     *
     * @return String
     */
    protected function getInputFilePath(): String
    {
        return Flytt::CONVERT . $this->getJobb()->getFil()->getNavn();
    }

    /**
     * Hent full filbane til hvor ferdigkonvertert fil skal lagres
     *
     * @return String
     */
    public function getOutputFilePath(): String
    {
        return Flytt::STORE . $this->getFilnavn();
    }

    /**
     * Hent full filbane til x264 data-fil
     *
     * @return String
     */
    public function getX264FilePath(): String
    {
        return Flytt::x264 . $this->getJobb()->getFil()->getNavnUtenExtension() . '_x264data.txt';
    }

    /**
     * Hent temp log path
     *
     * @return String
     */
    protected function getLogPath(): String
    {
        return Converter::DIR_TEMP
            . Logger::DIR_LOG
            . $this->getJobb()->getFil()->getNavnUtenExtension()
            . static::getFileSuffix();
    }

    /**
     * Hent temp log path for first pass
     *
     * @return String
     */
    public function getFirstPassLogPath(): String
    {
        return $this->getLogPath() . '_firstpass.txt';
    }

    /**
     * Hent temp log path for second pass
     *
     * @return String
     */
    protected function getSecondPassLogPath(): String
    {
        return $this->getLogPath() . '_secondpass.txt';
    }
    
    /**
     * Hent temp log path for second pass
     *
     * @return String
     */
    protected function getImageLogPath(): String
    {
        return $this->getLogPath() . '_image.txt';
    }

    /**
     * Get video bitrate
     *
     * @return integer
     */
    public static function getVideoBitrate(): int
    {
        return static::VIDEO_BITRATE;
    }

    /**
     * Get audio bitrate
     *
     * @return integer
     */
    public static function getAudioBitrate(): int
    {
        return static::AUDIO_BITRATE;
    }

    /**
     * Get audio samplingrate
     *
     * @return integer
     */
    public static function getAudioSamplingrate(): int
    {
        return static::AUDIO_SAMPLINGRATE;
    }

    /**
     * Get file suffix
     *
     * @return String
     */
    public static function getFileSuffix(): String
    {
        return static::FILE_ID;
    }

    /**
     * Get file extension
     *
     * @return String
     */
    public static function getFileExt(): String
    {
        return static::FILE_EXT;
    }

    /**
     * Get full file suffix (including extension)
     *
     * @return String
     */
    public static function getSuffix(): String
    {
        return static::getFileSuffix() . static::getFileExt();
    }
}
