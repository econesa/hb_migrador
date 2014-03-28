<?php
/**
** AD_Window
***/
include_once '../../models/AdWindow.php';
include_once '../../models/AdTab.php';
include_once '../../models/AdTable.php';
include_once '../../models/AdColumn.php';
include_once '../../models/AdReference.php';
include_once '../../models/AdField.php';
include_once '../../models/TAdMig.php';
include_once '../../utils.php';

session_start(); 

$data = json_decode( file_get_contents('php://input'), true );

$save_changes =  true; //  false; //
$win_obj = new AdWindow();
$tab_obj = new AdTab();
$table_obj = new AdTable();
$fld_obj = new AdField();


$last_id_parent = $win_obj->cLastID() + 1;
$last_id_child  = $tab_obj->cLastID() + 1;
$last_id_gchild = $fld_obj->cLastID() + 1;

foreach ($data as $item)
{
	// se buscan los datos completos de la fila
	$values_array = $win_obj->cFindByExpression( $item['UPPER(NAME)'] );

	// se busca el id original de la tabla para buscar las pestañas
	$id_old  = $values_array['AD_WINDOW_ID'];
	//echo "<br/> AD_WIN: $id_old -> $last_id_parent <br/>";

	$win_obj->cMigrateByName( $item['UPPER(NAME)'], $last_id_parent, $save_changes );	
	
	$children_array = $tab_obj->cFindByParentID( $id_old );
	foreach ($children_array as $childname)
	{
		
		// se buscan los datos completos de la fila
		$child_values_array = $tab_obj->cFindByExpression( $childname, $id_old );
		// se guarda el id original de la tabla para buscar las pestañas
		$child_id_old    = $child_values_array['AD_TAB_ID'];

		$tab_obj->cMigrateByName( $childname, $last_id_child, $id_old, $save_changes );

		$gchildren_array = $fld_obj->cFindByParentID( $child_id_old );
		foreach ($gchildren_array as $gcname)
		{
			$fld_obj->cMigrateByName( $gcname, $last_id_gchild, $child_id_old, $save_changes );

			$last_id_gchild++;
		}

		$last_id_child++;
		
	} // end foreach	

	$last_id_parent++;	
	
} // end foreach 

?>