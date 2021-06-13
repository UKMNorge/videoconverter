<?php

namespace UKMNorge\Videoconverter\Store;

use Exception;
use UKMNorge\Http\Curl;
use UKMNorge\Http\CurlFileUploader;
use UKMNorge\Videoconverter\Converter;
use UKMNorge\Videoconverter\Database\Query;
use UKMNorge\Videoconverter\Jobb;
use UKMNorge\Videoconverter\Utils\Logger;
use UKMNorge\Videoconverter\Versjon\Versjon;

class Store
{

    const MAX_TRANSFER_TIME = 60; // seconds

    /**
     * Kjører det allerede en lagringsjobb?
     *
     * @return boolean
     */
    public static function isRunning(): bool
    {

        $query = new Query(
            "SELECT `id` FROM `ukmtv`
            WHERE `status_progress` = 'transferring'
            LIMIT 1",
            [],
            'videoconverter'
        );

        return !!$query->getField();
    }

    /**
     * Start lagringen av en jobb
     * 
     * Overfører automatisk alle versjoner av filen som konverteren støtter
     *
     * @return bool
     */
    public static function startNext(): bool
    {
        # Finn neste jobb, og oppdater status om at vi er i gang
        $jobb = static::getNext();
        $jobb->saveStatus('transferring');

        # Sett opp loggeren
        Logger::setId('STORE');
        Logger::setCron($jobb->getId());

        # Overfør de ulike utgavene av filen
        foreach (Converter::getVersjoner($jobb) as $versjon) {
            Logger::log('SEND VERSJON ' . get_class($versjon) . ' TIL ' .  Converter::getStorageServerEndpoint());
            static::transfer($versjon);
        }

        # Overføring ferdig, oppdater databasen
        Logger::log('Alle versjoner sendt til videostorage');
        $jobb->saveStatus('transferred');

        # Varsle UKM.no om at filen er ferdig
        Logger::log('Notify ' . UKM_HOSTNAME);
        try {
            static::register($jobb);
        } catch (Exception $e) {
            Logger::log('IKKE REGISTRERT!');
        }

        # Avgjør hva som skjer videre med jobben
        # Cron-jobber følger opp dette basert på database-info
        static::decideNextStep($jobb);

        # Slett midlertidige filer
        Logger::log('Cleanup');
        static::cleanup($jobb);

        return true;
    }

    private static function cleanup(Jobb $jobb): bool
    {
        $filer = [];

        foreach (Converter::getVersjoner($jobb) as $versjon) {
            $filer[] = $versjon->getFirstPassLogPath();
            $filer[] = $versjon->getSecondPassLogPath();
            $filer[] = $versjon->getOutputFilePath();
        }


        foreach ($filer as $fil) {
            Logger::log('SLETT: ' . $fil);
            if (file_exists($fil)) {
                Logger::log('TOOD: faktisk slett fila da');
                #unlink($fil);
            }
        }

        return true;
    }

    /**
     * Avgjør hva som er neste steg for jobben
     * 
     * Oppdaterer databasen, og overlater oppfølging til en kollega-cron
     *
     * @param Jobb $jobb
     * @return bool true
     */
    private static function decideNextStep(Jobb $jobb)
    {
        if ($jobb->getDatabaseData()['status_final_convert'] != 'complete') {
            Logger::log('Next step: convert more');
            $jobb->saveStatus('converting');
            // convert_final.cron will follow up
        } else {
            Logger::log('Next step: archive convert');
            $jobb->saveStatus('archive');
            // convert_archive.cron will follow up
        }

        return true;
    }

    /**
     * Send info til UKM.no / NS1 for registrering
     *
     * @param Jobb $jobb
     * @throws Exception
     * @return bool true
     */
    private static function register(Jobb $jobb)
    {
        $url = Converter::getVideoRegistrationEndpoint($jobb->getId());

        Logger::log($url);
        foreach ($jobb->getDatabaseData() as $key => $val) {
            Logger::log('SEND(' . $key . ') => ' . var_export($val, true));
        }
        $registrer = new Curl();
        $registrer->post($jobb->getDatabaseData());
        $registrer->timeout(date('G') < 5 ? 30 : 20); // Lengre timeout om natta når serveren tar backup
        $result = $registrer->request($url);

        Logger::log(UKM_HOSTNAME . ' svarte:');
        Logger::log($result);

        if (is_object($result)) {
            if ($result && isset($result->success) && $result->success) {
                Logger::log('Success');
                return true;
            }

            throw new Exception(
                Logger::log(
                    'Kunne ikke registrere filen i UKM-TV.'
                )
            );
        }

        throw new Exception(
            Logger::log(
                'Fikk ikke kontakt med ' . UKM_HOSTNAME . '.'
            )
        );
    }

    /**
     * Faktisk overfør filen til lagringsserveren
     *
     * @param Versjon $versjon
     * @return void
     */
    private static function transfer(Versjon $versjon)
    {
        $fil = $versjon->getOutputFilePath();
        $data = static::getFileDataArray($versjon);

        Logger::log('FIL:');
        Logger::log($fil);
        Logger::log('DATA:');
        Logger::log($data);

        $curl = new CurlFileUploader(
            $fil,     # File to send
            'file',   # Name of files-array
            $data     # File metadata
        );

        $result = $curl->request(
            Converter::getStorageServerEndpoint()      # Url to send to
        );

        Logger::log('Svar fra videostorage:');
        Logger::log($result);
        if (!is_object($result)) {
            $versjon->getJobb()->saveStatus('crashed');
            throw new Exception(
                Logger::notify('Fikk ikke kontakt med videostorage')
            );
        }

        if ($result->success) {
            Logger::log(get_class($versjon) . ' lagret');
            return true;
        } else {
            $versjon->getJobb()->saveStatus('crashed');
            throw new Exception(
                Logger::notify('Kunne ikke lagre ' . get_class($versjon))
            );
        }
    }

    /**
     * Hent informasjon om filen som skal sendes
     *
     * @param Versjon $versjon
     * @return Array<string>
     */
    private static function getFileDataArray(Versjon $versjon): array
    {
        $timestamp = time();
        $file_hash = hash_file('sha256', $versjon->getOutputFilePath());
        $signature = static::sign($file_hash, $versjon, $timestamp);
        Logger::log('SIGN:' . $signature);


        return [
            'file_name' => $versjon->getFilnavn(),                      # Filnavn som filen skal ha
            'file_path' => $versjon->getJobb()->getFil()->getBane(),    # Filbane hvor filen skal ligge
            'file_hash' => $file_hash,                                  # Hash av lokal fil
            'sign'      => $signature,                                  # Concat sign av alle verdier
            'timestamp' => $timestamp                                   # Tidspunkt for signering
        ];
    }

    public static function completed()
    {
        // Settings status to transferring
        // End of script will set status back to
        // a) converting (if status_final_convert not is complete)
        // b) archive (if status_final_convert is complete)
        // Script convert_final.cron will follow up on case a
        // Script archive.cron will follow up on case b

    }

    /**
     * Signer data som sendes
     * 
     * Videostorage tar kun i mot forespørsler som er riktig signert, 
     * hvis ikke kunne den blitt misbrukt herfra, til månen og tilbake.
     * (Og det er jo ikke helt heldig)
     *
     * @param Versjon $versjon
     * @param Int $timestamp
     * @return String
     */
    private static function sign(String $file_hash, Versjon $versjon, Int $timestamp): String
    {
        $message = 'file_path=' . $versjon->getJobb()->getFil()->getBane() .
            '&file_hash=' . $file_hash .
            '&timestamp=' . $timestamp;

        Logger::log('MESSAGE:' . $message);
        return hash_hmac(
            'sha256',
            $message,
            UKM_VIDEOSTORAGE_UPLOAD_KEY
        );
    }

    /**
     * Hent neste overføringsjobb
     *
     * @return Jobb
     */
    public static function getNext(): Jobb
    {
        $query = new Query(
            "SELECT * FROM `" . Converter::TABLE . "`
            WHERE `status_progress` = 'store'
            ORDER BY `id` ASC
            LIMIT 1",
            [],
            'videoconverter'
        );

        $cron_id = $query->getField();

        if (!$cron_id) {
            throw new Exception(
                'Fant ikke ny lagringsjobb som skal overføres'
            );
        }

        return new Jobb($cron_id);
    }
}
