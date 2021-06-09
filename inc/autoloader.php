<?php

use UKMNorge\Database\SQL\DB;

# Autoloader for UKMlib
require_once('UKM/Autoloader.php');

# Autoloader for UKMNorge\Videoconverter
spl_autoload_register(function ($class_name) {
    if (strpos($class_name, 'UKMNorge\Videoconverter\\') === 0) {
        $file = dirname(__FILE__) . str_replace(
            ['\\', 'UKMNorge/Videoconverter/'],
            ['/', ''],
            $class_name
            ) . '.php';
            
            if (file_exists($file)) {
                require_once($file);
            } else {
                echo 'Tried to find ' . $file;
        }
    }
});

# Bruk riktig database
DB::setDatabase('videoconverter');