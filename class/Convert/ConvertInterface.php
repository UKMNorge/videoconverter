<?php

namespace UKMNorge\Videoconverter\Convert;

use UKMNorge\Videoconverter\Jobb;

interface ConvertInterface
{
    public static function getNextQueryWhere(): String;
    public static function getVersjoner( Jobb $jobb ) : array;
}
