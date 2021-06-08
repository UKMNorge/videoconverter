<?php

namespace UKMNorge\Videoconverter;

use Exception;
use UKMNorge\Database\SQL\Insert;
use UKMNorge\Database\SQL\Update;
use UKMNorge\Database\SQL\Query;

class Jobb
{

    const TABLE = 'ukmtv';

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
     * Registrer en konverteringsjobb
     *
     * @param Eier $eier
     * @param String $video_type
     * @param Int $innslagId
     * 
     * @throws Exception
     * 
     * @return Job
     */
    public static function registrer(Eier $eier, Film $film, String $filbane): Job
    {
        // Oppretter jobben

        $query = new Insert(static::TABLE);
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

        // BEREGNER FILBANER
        // Siden denne er avhengig av JobbId (cron_id), 
        // kjøres det en update-query nedenfor
        $filbane = Fil::finnFilbane( $eier, $film );
        $filnavn = Fil::finnFilnavn( $eier, $film, $cron_id, Fil::finnExtension($filbane) );
        $this->fil = new Fil( $filbane, $filnavn );

        // HENTER DETALJER OM FILFORMAT
        $this->getFilm()->beregnDetaljerFraFil( $this->getFil() );
        
        // FLYTT FILEN
        $this->getFil()->flytt()->tilConvert();

        // OPPDATERER DATABASEN
        $update = new Update(static::TABLE,['id' => $cron_id]);

        $update->add('file_name',       $this->getFil()->getNavn());
        $update->add('file_path',       $this->getFil()->getBane());
        $update->add('file_type',       '.'.$this->getFil()->getExtension());
        
        $update->add('file_width',      $this->getFilm()->getBredde());
        $update->add('file_height',     $this->getFilm()->getHoyde());
        $update->add('file_duration',   $this->getFilm()->getVarighet());
        $update->add('pixel_format',    $this->getFilm()->getPikselFormat());

        $update->add('status_progress', 'registered');

        $res = $update->run();

        if( !$res ) {
            throw new Exception(
                'Klarte ikke å lagre detaljer om filmen'
            );
        }


        return new static($res);
    }

    /**
     * Hent en jobb fra databasen
     *
     * @param Int $cron_id
     * @return Job
     */
    public function __construct(Int $cron_id)
    {
        $query = new Query(
            "SELECT * 
            FROM `#table`
            WHERE `id` = '#id'
            ",
            [
                'table' => static::TABLE,
                'id' => $cron_id
            ]
        );
        $data = $query->getRow();

        if (!$data) {
            throw new Exception(
                'Fant ikke CRON ID ' . $cron_id
            );
        }

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
            $data->get
        )
    }


    /**
     * Hent info om eieren
     *
     * @return Eier
     */
    public function getEier(): Eier {
        return $this->eier;
    }

    /**
     * Hent info om filmen
     *
     * @return Film
     */
    public function getFilm(): Film {
        return $this->film;
    }

    /**
     * Hent detaljer om filen
     *
     * @return Fil
     */
    public function getFil(): Fil {
        return $this->fil;
    }
}