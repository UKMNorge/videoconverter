<?php

use UKMNorge\Videoconverter\Database\Query;
use UKMNorge\Videoconverter\Database\Insert;

ini_set('display_errors', true);

# Autoloader for UKMlib
require_once('UKMconfig.inc.php');
require_once('UKM/Autoloader.php');

# Autoloader for UKMNorge\Videoconverter
spl_autoload_register(function ($class_name) {
    if (strpos($class_name, 'UKMNorge\Videoconverter\\') === 0) {
        $file = dirname(dirname(__FILE__)) .'/class/' . str_replace(
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