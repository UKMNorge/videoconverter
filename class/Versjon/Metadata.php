<?php

namespace UKMNorge\Videoconverter\Versjon;

use stdClass;
use UKMNorge\Videoconverter\Jobb;
use UKMNorge\Videoconverter\Utils\API;
use UKMNorge\Videoconverter\Utils\Logger;

class Metadata extends Versjon
{
    const FILE_ID = '';
    const EXT = '.metadata.txt';
    const FFMPEG = false;

    public function execute(Jobb $jobb)
    {
        Logger::log('WRITE METADATA TO: ' . $this->getOutputFilePath());
        $metadata = static::appendMetadata(API::getVideoInfo($jobb));
        Logger::log($metadata);

        $file = fopen($this->getOutputFilePath(), 'w');
        fwrite($file, $metadata);
        fclose($file);
    }

    /**
     * Append metadata
     *
     * @param stdClass $object
     * @param integer $indent
     * @return string
     */
    private static function appendMetadata($object, $indent = 0): string
    {
        $text = '';
        if (is_object($object) or is_array($object)) {
            foreach ($object as $key => $value) {
                if (is_object($value) or is_array($value)) {
                    $text .= str_repeat(' ', $indent) . strtoupper($key) . ": \r\n";
                    $text .= static::appendMetadata($value, ($indent + 1));
                } else {
                    $text .= str_repeat(' ', $indent) . strtoupper($key) . ": " . $value . "\r\n";
                }
            }
        }
        return $text;
    }

    /**
     * Hent midlertidige filer som skal slettes etter "konvertering"
     *
     * @param Jobb $jobb
     * @return array
     */
    public static function getFilesToDelete(Jobb $jobb): array
    {
        return [];
    }

    /**
     * Må defineres på grunn av interfacet som Versjon implementerer
     *
     * @param String $preset
     * @return String
     */
    public function getFFmpegKall(String $preset = null): String
    {
        return '';
    }
}
