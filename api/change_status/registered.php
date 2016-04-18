<?php
logg('Restart konverteringsprossen helt');
ukmtv_update('status_progress','registered', CRON_ID);
ukmtv_update('status_first_convert','', CRON_ID);
ukmtv_update('status_final_convert','', CRON_ID);
ukmtv_update('status_archive','', CRON_ID);
ukmtv_update('admin_notice','false', CRON_ID);


header('Content-Type: application/json');
die( json_encode( array('action'=>'registered', 'success'=>true) ) );