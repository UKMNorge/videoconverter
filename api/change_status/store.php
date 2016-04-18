<?php
logg('Restart lagringsprosess');
ukmtv_update('status_progress','store', CRON_ID);
ukmtv_update('admin_notice','false', CRON_ID);


header('Content-Type: application/json');
die( json_encode( array('action'=>'store', 'success'=>true) ) );