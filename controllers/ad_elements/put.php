<?php
include_once '../../models/AdElement.php';

session_start(); 

$data = json_decode( file_get_contents('php://input'), true );

$save_changes = true;

$elem_obj   = new AdElement();
$elem_obj->load();

$last_id = $elem_obj->cLastID() + 1;

foreach ($data as $item)
{
	$elem_obj->cMigrateByName( $item['UPPER(COLUMNNAME)'], $last_id, $save_changes );

	$last_id++;
}

?>