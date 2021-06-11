<?php

namespace UKMNorge\Videoconverter;

use Exception;
use UKMNorge\Videoconverter\Database\Query;
use UKMNorge\Videoconverter\Database\Insert;
use UKMNorge\Videoconverter\Database\Update;

use UKMNorge\Videoconverter\Jobb\Eier;
use UKMNorge\Videoconverter\Jobb\Film;
use UKMNorge\Videoconverter\Jobb\Fil;

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
    public static function registrer(Eier $eier, Film $film, String $filbane): Jobb
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
        $filnavn = Fil::finnFilnavn($eier, $film, $cron_id, Fil::finnExtension($filbane));
        $jobb->fil = new Fil($filbane, $filnavn);

        // HENTER DETALJER OM FILFORMAT
        $jobb->getFilm()->beregnDetaljerFraFil($jobb->getFil());

        // FLYTT FILEN
        $jobb->getFil()->flytt()->tilConvert();

        // OPPDATERER DATABASEN
        $update = new Update(Converter::TABLE, ['id' => $cron_id]);

        $update->add('file_name',       $jobb->getFil()->getNavn());
        $update->add('file_path',       $jobb->getFil()->getBane());
        $update->add('file_type',       '.' . $jobb->getFil()->getExtension());

        $update->add('file_width',      $jobb->getFilm()->getBredde());
        $update->add('file_height',     $jobb->getFilm()->getHoyde());
        $update->add('file_duration',   $jobb->getFilm()->getVarighet());
        $update->add('pixel_format',    $jobb->getFilm()->getPikselFormat());

        $update->add('status_progress', 'registered');

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
        $data = $query->getRow();

        if (!$data) {
            throw new Exception(
                'Fant ikke CRON ID ' . $cron_id
            );
        }

        $this->id = (int) $data->id;

        $this->eier = new Eier(
            (int) $data->blog_id,
            (int) $data->pl_id,
            (int) $data->season
        );

        $this->film = new Film(
            $data->type,
            $data->b_id
        );

        $this->fil = new Fil(
            $data->file_path,
            $data->file_name
        );

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
        $query = new Update(Converter::TABLE, ['id' => $this->getId()]);
        $query->add('status_progress', $status);
        $query->run();
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
     *
     * @return Array
     */
    public function getDatabaseData(): array
    {
        return $this->data;
    }
}
