<?php

namespace UKMNorge\Videoconverter\Utils;

class FFProbe
{

    /**
     * Hent filmens størrelse
     *
     * @param String $file_path
     * @return array<float width, float $height>
     */
    public static function getDimensions(String $file_path): array
    {
        $probe_width = "ffprobe -show_streams '$file_path' 2>&1 | grep ^width | sed s/width=//";
        $probe_height = "ffprobe -show_streams '$file_path' 2>&1 | grep ^height | sed s/height=//";
        $file_width = exec($probe_width);
        $file_height = exec($probe_height);

        return [
            'width' => (float) $file_width, 
            'height' => (float) $file_height
        ];
    }

    /**
     * Hent filmens varighet
     *
     * @param String $file_path
     * @return integer
     */
    public static function getDuration(String $file_path): int
    {
        return (int) exec("ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 '$file_path'");
    }

    /**
     * Hent filmens format
     *
     * @param String $file_path
     * @return void
     */
    public static function getFormat(String $file_path)
    {
        return exec("ffprobe -show_entries stream=pix_fmt -of default=noprint_wrappers=1:nokey=1 '$file_path'");
    }
}