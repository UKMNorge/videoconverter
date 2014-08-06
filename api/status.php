<?php
// UKMvideo vil curle denne adressen ved hver visning for å se status på videoconverter
// Introdusert 2013

$info = new stdClass;
$info->diskspace = diskfreespace("/");


die(json_encode($info));