<?php

namespace UKMNorge\Videoconverter\Utils;

class Timer {

    private $start;
    private $stop;
    private $name;

    /**
     * Start a new timer
     */
    public function __construct( String $name )
    {
        $this->start = microtime(true);
        $this->name = $name;
    }

    /**
     * Stop the timer
     *
     * @return float
     */
    public function stop(): float {
        return $this->getDuration();
    }

    /**
     * Get the duration between start and stop
     *
     * @return float
     */
    public function getDuration(): float {
        if( is_null($this->stop)) {
            $this->stop = microtime(true);
        }
        return $this->stop - $this->start;
    }

    /**
     * Get the name and duration of the timer
     *
     * @return string
     */
    public function __toString()
    {
        return $this->name .': '. $this->getDuration();
    }
}