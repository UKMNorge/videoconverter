<?php
header('Access-Control-Allow-Headers: Content-Type, Content-Range, Content-Disposition');
header('Access-Control-Allow-Origin: https://' . UKM_HOSTNAME);
header('Access-Control-Request-Method: OPTIONS, HEAD, GET, POST, PUT, PATCH, DELETE');
header('Access-Control-Allow-Credentials: true');