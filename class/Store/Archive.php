<?php

namespace UKMNorge\Videoconverter\Store;

use Exception;
use UKMNorge\Videoconverter\Converter;
use UKMNorge\Videoconverter\Jobb;
use UKMNorge\Videoconverter\Utils\API;
use UKMNorge\Videoconverter\Utils\Logger;
use UKMNorge\Videoconverter\Versjon\Arkiv;
use UKMNorge\Videoconverter\Versjon\Bilde;
use UKMNorge\Videoconverter\Versjon\Metadata;
use UKMNorge\Videoconverter\Versjon\Versjon;

class Archive extends Store
{

    const DB_STATUS_TRIGGER = 'archive';
    const DB_STATUS_ACTIVE = 'archiving';
    const DB_STATUS_DONE = 'complete';


    public static function transfer(Versjon $versjon): void
    {
        # Hent info om filmen
        # Dette avgjør hvor vi skal lagre filene på arkiv-serveren
        $info = API::getVideoInfo($versjon->getJobb());

        if (!$info) {
            throw new Exception(
                Logger::log(
                    'Kunne ikke hente info om film for arkivering'
                )
            );
        }

        $path = $info->path->dir;
        $filnavn = $info->path->filename;

        # Sørg for at mappen finnes på arkiv-serveren
        if (!is_dir(Converter::DIR_ARCHIVE . $path)) {
            Logger::log('Oppretter lagrings-path: ' . Converter::DIR_ARCHIVE . $path);
            mkdir(Converter::DIR_ARCHIVE . $path, 0755, true);
        }

        # Kopier filen
        Logger::log('FLYTT: ');
        Logger::log('  FRA: ' . $versjon->getOutputFilePath());
        Logger::log('  TIL: ' . $path . $filnavn . $versjon->getFileExt());
        copy(
            $versjon->getOutputFilePath(),
            Converter::DIR_ARCHIVE . $path . $filnavn . $versjon->getFileExt()
        );
    }


    /**
     * Hent hvilke versjoner skal sendes til lagringsserveren
     *
     * @param Jobb $jobb
     * @return array
     */
    protected static function getVersjoner(Jobb $jobb): array
    {
        // FETCH METADATA-fil
        // Returner 
        return [
            new Arkiv($jobb),
            new Metadata($jobb),
            new Bilde($jobb)
        ];
    }

    /**
     * Avgjør hvilke filer som skal slettes når lagring er gjennomført
     *
     * @param Jobb $jobb
     * @return array<String>
     */
    protected static function getFilesToDelete(Jobb $jobb): array
    {
        $filer = parent::getFilesToDelete($jobb);

        foreach (static::getVersjoner($jobb) as $versjon) {
            $filer[] = $versjon->getOutputFilePath();
        }

        return $filer;
    }

    /**
     * Varsle andre tjenester om at arkiveringen er gjort?
     *
     * @param Jobb $jobbprotected
     * @return void
     */
    protected static function notify(Jobb $jobb): void
    {
        // TODO: skal denne gjøre noe?

    }

    public static function decideNextStep(Jobb $jobb): void
    {
        // Ingenting som skal gjøres - konverteringen er ferdig
    }
}
