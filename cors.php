<?php

require_once('UKMconfig.inc.php');
require_once('inc/headers.inc.php');
require_once('inc/jQupload_handler.inc.php');

$upload_handler = new UploadHandler();
die($upload_handler->get_body());
?>