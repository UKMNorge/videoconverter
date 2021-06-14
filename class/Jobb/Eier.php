<?php

namespace UKMNorge\Videoconverter\Jobb;

class Eier
{

    private $arrangementId;
    private $bloggId;
    private $sesong;

    /**
     * Opprett eier-objekt
     *
     * @param Int $bloggId
     * @param Int $arrangementId
     * @param Int $sesong
     */
    public function __construct(Int $bloggId, Int $arrangementId, Int $sesong)
    {
        $this->arrangementId = $arrangementId;
        $this->bloggId = $bloggId;
        $this->sesong = $sesong;
    }

    /**
     * Hent arrangement ID
     *
     * @return integer
     */
    public function getArrangementId(): Int
    {
        return $this->arrangementId;
    }

    /**
     * Hent blogg ID
     *
     * @return integer
     */
    public function getBloggId(): Int
    {
        return $this->bloggId;
    }

    /**
     * Hent hvilken sesong filmen er lastet opp
     *
     * @return integer
     */
    public function getSesong(): Int
    {
        return $this->sesong;
    }
}
