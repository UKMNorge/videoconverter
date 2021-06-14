<?php

namespace UKMNorge\Videoconverter\Jobb;

use Exception;
use UKMNorge\Videoconverter\Utils\FFProbe;

class Film
{

    private $type;
    private $innslag_id;
    
    private $width;
    private $height;
    private $duration;
    private $pikselformat;

    /**
     * Opprett nytt film-objekt
     * 
     * Inneholder info om innholdet i filmen
     *
     * @param 'reportasje'|'innslag' $type 
     * @param Int $innslag_id
     */
    public function __construct(String $type, Int $innslag_id, Array $data = null)
    {
        if (!in_array($type, ['reportasje', 'innslag'])) {
            throw new Exception(
                'Ukjent type film: ' . $type
            );
        }
        $this->type = $type;
        $this->innslag_id = $innslag_id;

        if( is_array($data) ) {
            $this->height = $data['file_width'];
            $this->width = $data['file_height'];
            $this->duration = $data['file_duration'];
            $this->pikselformat = $data['pixel_format'];
        }
    }

    /**
     * Hvilken type film er det?
     *
     * @return 'reportasje'|'innslag'
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Hvilket innslag handler filmen om
     *
     * @return int
     */
    public function getInnslagId(): int
    {
        return $this->innslag_id;
    }


    /**
     * Hent detaljer om filmen fra filen ved hjelp av ffprobe
     *
     * @param Fil $fil
     * @return void
     */
    public function beregnDetaljerFraFil( String $file ): void {
        $dimensions = FFProbe::getDimensions( $file );
        $this->width = (int) $dimensions['width'];
        $this->height = (int) $dimensions['height'];

        $this->duration = (int) FFProbe::getDuration($file);
        
        $this->pikselformat = FFProbe::getFormat($file);
    }

    /**
     * Hent filmens høyde
     *
     * @return int
     */
    public function getHoyde(): Int {
        return $this->height;
    }

    /**
     * Hent filmens bredde
     *
     * @return Int
     */
    public function getBredde(): Int {
        return $this->width;
    }

    /**
     * Hent størrelsesforholdet mellom bredde og høyde
     *
     * @return float
     */
    public function getStorrelseForhold(): float {
        return $this->getBredde() / $this->getHoyde();
    }

    /**
     * Beregn bredde for en gitt høyde
     *
     * @param Int $hoyde
     * @return Int
     */
    public function getBreddeForHoyde( Int $hoyde ) : Int {
        $bredde = round( $this->getStorrelseForhold() * $hoyde );
        # Rund ned til nærmeste partall
        if( $bredde % 2 != 0 ) {
            $bredde--;
        }
        return $bredde;
    }

    /**
     * Hent oppløsningen for en gitt høyde
     *
     * @param Int $hoyde
     * @return String
     */
    public function getOpplosningForHoyde( Int $hoyde ) : String {
        return $this->getBreddeForHoyde( $hoyde ) .'x'. $hoyde;
    }

    /**
     * Hent filmens varighet (sekunder)
     *
     * @return Int
     */
    public function getVarighet(): Int {
        return $this->duration;
    }

    /**
     * Hvor i tidslinjen skal vi hente bildet fra?
     * 
     * @return Int
     */
    public function getBildePosisjon() : Int {
        if( $this->getVarighet() > 8 ) {
            return 8;
        }

        $position = floor( $this->getVarighet() * 0,1 );

        if( $position < 1 ) {
            return 1;
        }
        
        return $position;
    }

    /**
     * Hent filmens format (yuv-something stort set)
     *
     * @return String
     */
    public function getPikselFormat(): String {
        return $this->pikselformat;
    }
}
