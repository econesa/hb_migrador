<?php
include_once '../../models/AdProcess.php';

session_start(); 

$data = json_decode( file_get_contents('php://input'), true );

$save_changes = true;

$entity_obj  =  new AdProcess();

$last_id = $entity_obj->cLastID() + 1;

foreach ($data as $item)
{
	$entity_obj->cMigrateByName( $item['UPPER(VALUE)'], $last_id, $save_changes );

	$last_id++;
}

?>