<?php

namespace UKMNorge\Videoconverter\Jobb;

use UKMNorge\Videoconverter\Version;
use UKMNorge\Videoconverter\Versjon\Arkiv;
use UKMNorge\Videoconverter\Versjon\HD;
use UKMNorge\Videoconverter\Versjon\Mobil;

class Fil
{
    private $navn;
    private $bane;
    private $extension;

    /**
     * Opprett nytt filobjekt
     *
     * @param String $filbane
     * @param String $filnavn
     * @return Fil
     */
    public function __construct(String $filbane, String $filnavn)
    {
        $this->bane = $filbane;
        $this->navn = $filnavn;
        $this->extension = static::finnExtension($this->navn);
        $this->flytt = new Flytt($this);
    }

    /**
     * Returner flytte-hjelperen
     *
     * @return Flytt
     */
    public function flytt(): Flytt
    {
        return $this->flytt;
    }

    /**
     * Hent filbane (path)
     *
     * @return String
     */
    public function getBane(): String
    {
        return $this->bane;
    }

    /**
     * Hent filnavn
     *
     * @return String
     */
    public function getNavn(): String
    {
        return $this->navn;
    }

    /**
     * Hent filnavn uten extension
     *
     * @return String
     */
    public function getNavnUtenExtension(): String
    {
        return rtrim($this->getNavn(), $this->getExtension());
    }

    /**
     * Hent full filbane (og navn)
     *
     * @return String
     */
    public function getFil(): String
    {
        return $this->getBane() . $this->getNavn();
    }

    /**
     * Hent filextension
     *
     * @return String
     */
    public function getExtension(): String
    {
        return $this->extension;
    }

    /**
     * Finn filbane hvor filen skal lagres ut fra eier og film
     * 
     * Returnerer med trailing slash
     *
     * @param Eier $eier
     * @param Film $film
     * @return String $filbane
     */
    public static function finnFilbane(Eier $eier, Film $film): String
    {
        return $eier->getSesong() . '/' . $eier->getArrangementId() . '/' . $film->getType() . '/';
    }

    /**
     * Finn filnavn
     *
     * @param Eier $eier
     * @param Film $film
     * @param Int $cron_id
     * @param String $extension
     * @return String
     */
    public static function finnFilnavn(Eier $eier, Film $film, Int $cron_id, String $extension): String
    {
        return
            str_replace(
                '/',
                '_',
                static::finnFilbane($eier, $film)
            ) .
            $film->getInnslagId() .
            '_cron_' . $cron_id .
            $extension;
    }

    /**
     * Finn fil-extension fra filnavn
     *
     * @param String $filnavn
     * @return String $extension
     */
    public static function finnExtension(String $filnavn): String
    {
        return rtrim(strtolower(substr($filnavn, strrpos($filnavn, '.'))), '.');
    }
}
