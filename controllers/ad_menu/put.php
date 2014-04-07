<?php
include_once '../../models/AdMenu.php';

session_start(); 

$data = json_decode( file_get_contents('php://input'), true );

$save_changes = true;

$menu_obj   = new AdMenu();

$last_id = $menu_obj->cLastID() + 1;

foreach ($data as $item)
{
	$menu_obj->cMigrateByName( $item['UPPER(NAME)'], $last_id, $save_changes );

	$last_id++;
}

?>