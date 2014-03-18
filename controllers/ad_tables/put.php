<?php
/**
** AD_TABLE
***/
include '../../models/AdElement.php';
include '../../models/AdReference.php';
include '../../models/AdTable.php';
include '../../models/AdColumn.php';
include '../../models/TAdMig.php';
include '../../utils.php';

session_start(); 

//trunTable();
$data = json_decode( file_get_contents('php://input'), true );

$save_changes = true; // false; // 
$table_obj = new AdTable();
$col_obj   = new AdColumn();

$table_obj->load();
$col_obj->load();

$last_id_table = $table_obj->cLastID() + 1;
$last_id_col   = $col_obj->cLastID() + 1;

foreach ($data as $item)
{
	$values_array = $table_obj->cFindByExpression( $item[ 'UPPER(TABLENAME)' ] );
	
	// se busca el id original de la tabla para buscar las columnas	
	$id_old = $values_array['AD_TABLE_ID'];

	$table_obj->cMigrate( $values_array, $last_id_table, $save_changes );

	$columns_array = $col_obj->cFindByParentID( $id_old );
	foreach ($columns_array as $column)
	{
		$col_obj->cMigrate( $column, $last_id_col, $id_old, $save_changes );
		$last_id_col++;
	} // end foreach AD_Column

	$last_id_table++;
	
} // end foreach AD_Table

?>