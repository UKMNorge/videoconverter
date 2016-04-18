<?php
logg('Sett fil som deleted (does_not_exist)');
ukmtv_update('status_progress','does_not_exist', CRON_ID);
ukmtv_update('admin_notice','false', CRON_ID);

header('Content-Type: application/json');
die( json_encode( array('action'=>'delete', 'success'=>true) ) );