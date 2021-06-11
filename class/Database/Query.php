<?php

namespace UKMNorge\Videoconverter\Database;

use UKMNorge\Database\SQL\Query as UKMNorgeQuery;

class Query extends UKMNorgeQuery
{
    /**
     * @param string $query 
     * @param array $key_val_map 
     * @return void 
     */
    public function __construct(String $query, array $key_val_map = array())
    {
        parent::__construct($query, $key_val_map, 'videoconverter');
    }
}
