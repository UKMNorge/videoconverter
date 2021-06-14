<?php

namespace UKMNorge\Videoconverter\Database;

use UKMNorge\Database\SQL\Insert as UKMNorgeInsert;

class Insert extends UKMNorgeInsert
{
    /**
     * @param string $query 
     * @param array $key_val_map 
     * @return void 
     */
    public function __construct(String $table, array $key_val_map = array())
    {
        parent::__construct($table, $key_val_map, 'videoconverter');
    }
}
