<?php
/**
** AD_Window
***/
include_once '../../models/AdWindow.php';
include_once '../../models/AdTab.php';
include_once '../../models/AdField.php';
include_once '../../models/AdMenu.php';

session_start(); 

$data = json_decode( file_get_contents('php://input'), true );

$save_changes =  true; //  false; //
$win_obj   = new AdWindow();
$tab_obj   = new AdTab();
$fld_obj   = new AdField();
$menu_obj   = new AdMenu();

$last_id_menu = $menu_obj->cLastID() + 1;
$last_id_parent = $win_obj->cLastID() + 1;
$last_id_gchild = $fld_obj->cLastID() + 1;

foreach ($data as $item)
{
	// se buscan los datos completos de la fila
	$values_array = $win_obj->cFindByExpression( $item['UPPER(NAME)'] );

	// se busca el id original de la tabla para buscar las pestañas
	$id_old  = $values_array['AD_WINDOW_ID'];
	//echo "<br/> AD_WIN: $id_old -> $last_id_parent <br/>";

	$parent_id = $win_obj->cMigrateByName( $item['UPPER(NAME)'], $last_id_parent, $save_changes );	
	
	$children_array = $tab_obj->cFindByParentID( $id_old );
	foreach ($children_array as $childname)
	{		
		// se buscan los datos completos de la fila
		$child_values_array = $tab_obj->cFindByExpression( $childname, $id_old );
		
		$child_id_old = $tab_obj->cMigrateByName( $childname, $id_old, $parent_id, $save_changes );

		$gchildren_array = $fld_obj->cFindByParentID( $child_values_array['AD_TAB_ID'] );

		//echo '<br/> *+ *+ *+ *+*+*+*+*+*  Pestaña: ' . $childname . '*+*+*+*+* +* +* +* <br/>';
		//echo implode(", ", $gchildren_array) . '<br/>';

		foreach ($gchildren_array as $gcname)
		{
			$fld_obj->cMigrateByName( $gcname, $last_id_gchild, $child_values_array['AD_TAB_ID'], $child_id_old, $save_changes );

			$last_id_gchild++;
		} // end foreach 

	} // end foreach 
	$last_id_parent++;	

	
	//$menu_obj->cMigrateByName( $item['UPPER(NAME)'], $last_id_menu, $save_changes );
	
} // end foreach 



?>