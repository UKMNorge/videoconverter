<?php

require_once('UKMconfig.inc.php');

################################################
## SET ALL HEADERS AND ACTUALLY PERFORM UPLOAD 
header('Access-Control-Allow-Headers: true');
header('Access-Control-Allow-Origin: ' . UKM_HOSTNAME);
header('Access-Control-Request-Method: OPTIONS, HEAD, GET, POST, PUT, PATCH, DELETE');
header('Access-Control-Allow-Credentials: true');
//require('UploadHandler.php');
require_once('inc/jQupload_handler.inc.php');
$upload_handler = new UploadHandler();
die($upload_handler->get_body());
?>