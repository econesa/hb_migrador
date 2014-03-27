<?php
/**
** AD_TABLE
***/
include_once '../../models/AdElement.php';
include_once '../../models/AdReference.php';
include_once '../../models/AdTable.php';
include_once '../../models/AdColumn.php';
include_once '../../utils.php';

session_start(); 

//trunTable();
$data = json_decode( file_get_contents('php://input'), true );

$save_changes = true; // false; // 
$table_obj = new AdTable();
$col_obj   = new AdColumn();

$last_id_table = $table_obj->cLastID() + 1;
$last_id_col   = $col_obj->cLastID() + 1;

foreach ($data as $item)
{
	echo "<br>** migrando tabla {$item[ 'UPPER(TABLENAME)' ]}.... **<br>";
/*
	$values_array = $table_obj->cFindByExpression( $item[ 'UPPER(TABLENAME)' ] );
	// se busca el id original de la tabla para buscar las columnas	
	$id_old = $values_array['AD_TABLE_ID'];
*/
	$parent_id = $table_obj->cMigrateByName( $item[ 'UPPER(TABLENAME)' ], $last_id_table, $save_changes );
	
	$child_array = $col_obj->cFindByParentID( $parent_id );
	foreach ($child_array as $childname)
	{
		$col_obj->cMigrateByName( $childname, $parent_id, $last_id_col, $last_id_table, $save_changes );
		
		$last_id_col++;
	} // end foreach AD_Column
	
	$last_id_table++;
	
} // end foreach AD_Table

?>