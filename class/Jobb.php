<?php

namespace UKMNorge\Videoconverter;

use Exception;
use UKMNorge\Videoconverter\Database\Query;
use UKMNorge\Videoconverter\Database\Insert;
use UKMNorge\Videoconverter\Database\Update;

use UKMNorge\Videoconverter\Jobb\Eier;
use UKMNorge\Videoconverter\Jobb\Film;
use UKMNorge\Videoconverter\Jobb\Fil;
use UKMNorge\Videoconverter\Utils\Logger;

class Jobb
{

    const STATUS = ['registering', 'registered', 'converting', 'converted', 'store', 'transferring', 'transferred', 'archive', 'archiving', 'complete', 'crashed', 'does_not_exist'];

    /**
     * @var Eier
     */
    private $eier;

    /**
     * @var Film
     */
    private $film;

    /**
     * @var Fil
     */
    private $fil;

    /**
     * @var Int
     */
    private $id;

    /**
     * @var Array
     */
    private $data;

    /**
     * @var string
     */
    private $status;
    /**
     * Registrer en konverteringsjobb
     *
     * @param Eier $eier
     * @param String $video_type
     * @param Int $innslagId
     * 
     * @throws Exception
     * 
     * @return Jobb
     */
    public static function registrer(Eier $eier, Film $film, String $file): Jobb
    {
        // Oppretter jobben

        $query = new Insert(Converter::TABLE, [], 'videoconverter');
        $query->add('blog_id', $eier->getBloggId());
        $query->add('pl_id', $eier->getArrangementId());
        $query->add('season', $eier->getSesong());

        $query->add('type', $film->getType());
        $query->add('b_id', $film->getInnslagId());
        $query->add('status_progress', 'registering');

        $cron_id = $query->run();

        if (!$cron_id) {
            throw new Exception(
                'Kunne ikke opprette jobb'
            );
        }

        $jobb = new Jobb($cron_id);
        // BEREGNER FILBANER
        // Siden denne er avhengig av JobbId (cron_id), 
        // kjøres det en update-query nedenfor
        $filbane = Fil::finnFilbane($eier, $film);
        $filnavn = Fil::finnFilnavn($eier, $film, $cron_id, Fil::finnExtension($file));
        $jobb->fil = new Fil($filbane, $filnavn);

        // HENTER DETALJER OM FILFORMAT
        $jobb->getFilm()->beregnDetaljerFraFil($file);

        // FLYTT FILEN TIL CONVERT-MAPPA
        $jobb->getFil()->flytt()->tilConvert($file);

        // OPPDATERER DATABASEN
        $update = new Update(Converter::TABLE, ['id' => $cron_id]);

        $update->add('file_name',       $jobb->getFil()->getNavn());
        $update->add('file_path',       $jobb->getFil()->getBane());
        $update->add('file_type',       $jobb->getFil()->getExtension());

        $update->add('file_width',      $jobb->getFilm()->getBredde());
        $update->add('file_height',     $jobb->getFilm()->getHoyde());
        $update->add('file_duration',   $jobb->getFilm()->getVarighet());
        $update->add('pixel_format',    $jobb->getFilm()->getPikselFormat());

        $update->add('status_progress', 'registered');
        $update->add('file_name_store', $jobb->getFil()->getNavnUtenExtension() . '.mp4');

        $res = $update->run();

        if (!$res) {
            throw new Exception(
                'Klarte ikke å lagre detaljer om filmen'
            );
        }


        return $jobb;
    }

    /**
     * Hent en jobb fra databasen
     *
     * @param Int $cron_id
     * @return Jobb
     */
    public function __construct(Int $cron_id)
    {
        $query = new Query(
            "SELECT * 
            FROM `" . Converter::TABLE . "`
            WHERE `id` = '#id'
            ",
            [
                'id' => $cron_id
            ],
            'videoconverter'
        );
        $data = $query->getArray();

        if (!$data) {
            throw new Exception(
                'Fant ikke CRON ID ' . $cron_id
            );
        }

        $this->id = (int) $data['id'];

        $this->eier = new Eier(
            (int) $data['blog_id'],
            (int) $data['pl_id'],
            (int) $data['season']
        );

        $this->film = new Film(
            $data['type'],
            $data['b_id'],
            $data
        );

        $this->fil = new Fil(
            $data['file_path'],
            $data['file_name']
        );
        $this->status = $data['status_progress'];
        $this->data = $data;
    }

    /**
     * Oppdater jobbens overordnede status
     *
     * @param String $status
     * @throws Exception
     * @return void
     */
    public function saveStatus(String $status): void
    {
        if (!in_array($status, static::STATUS)) {
            throw new Exception(
                'Kan ikke sette jobb-status til ' . $status . ' da den ikke er støttet'
            );
        }
        $this->update('status_progress', $status);
    }

    /**
     * Fjern flagget om at admin må se over denne jobben
     *
     * @return boolean
     */
    public function resetAdminNotice(): bool
    {
        return $this->update('admin_notice', false);
    }

    /**
     * Start konverteringsjobben helt på nytt 😬
     *
     * @return bool
     */
    public function restart(): bool
    {
        Logger::log('RESTART JOBB');
        $update = new Update(
            Converter::TABLE,
            [
                'id' => $this->getId()
            ]
        );
        $update->add('status_progress', 'registered');
        $update->add('status_first_convert', NULL);
        $update->add('status_final_convert', NULL);
        $update->add('status_archive', NULL);
        $update->add('admin_notice', 'false');

        return !!$update->run();
    }

    /**
     * Merk jobben som slettet 👋
     *
     * @return boolean
     */
    public function delete(): bool {
        $this->saveStatus('does_not_exist');
        $this->resetAdminNotice();
        return true;
    }

    /**
     * Oppdater et databasefelt
     *
     * @param String $field
     * @param any $data
     * @return boolean
     */
    private function update(String $field, $data): bool
    {
        $query = new Update(Converter::TABLE, ['id' => $this->getId()]);
        $query->add($field, $data);
        $res = $query->run();
        return !!$res;
    }

    /**
     * Hent jobbens overordnede status
     *
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * Hent info om eieren
     *
     * @return Eier
     */
    public function getEier(): Eier
    {
        return $this->eier;
    }

    /**
     * Hent info om filmen
     *
     * @return Film
     */
    public function getFilm(): Film
    {
        return $this->film;
    }

    /**
     * Hent detaljer om filen
     *
     * @return Fil
     */
    public function getFil(): Fil
    {
        return $this->fil;
    }

    /**
     * Hent cron-jobbens ID
     *
     * @return Int
     */
    public function getId(): Int
    {
        return $this->id;
    }

    /**
     * Hent all info vi har om filmen
     * 
     * Burde kanskje begrense noe hva som gis ut her?
     * Hvis angitt key-parameter, vil den returnere verdi, eller
     * false hvis verdien ikke finnes
     *
     * @param String $key = null
     * @return Array
     */
    public function getDatabaseData(String $key = null): array
    {
        if (!is_null($key)) {
            if (isset($this->data[$key])) {
                return $this->data[$key];
            }
            return false;
        }
        return $this->data;
    }
}
