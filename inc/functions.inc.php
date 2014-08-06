<?php
function ukmtv_update($field, $status, $id) {	
	mysql_query("UPDATE `ukmtv` SET `".$field."` = '".$status."' WHERE `id` = '".$id."'") or die(mysql_error());
}
