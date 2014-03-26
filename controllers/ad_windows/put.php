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
$ref_obj = new AdReference();
$col_obj = new AdColumn();
$tmp_obj = new TAdMig( $save_changes );

$tab_obj->load();
$win_obj->load();
$fld_obj->load();
$ref_obj->load();
$col_obj->load();
$table_obj->load();

$last_id_parent = $win_obj->cLastID() + 1;
$last_id_child  = $tab_obj->cLastID() + 1;
$last_id_gchild = $fld_obj->cLastID() + 1;

foreach ($data as $item)
{
	// se buscan los datos completos de la fila
	$values_array = $win_obj->cFindByExpression( $item['UPPER(NAME)'] );

	// se busca el id original de la tabla para buscar las pestañas
	$id_old  = $values_array['AD_WINDOW_ID'];
	echo "<br/> AD_WIN: $id_old -> $last_id_parent <br/>";

	$win_obj->cMigrate( $values_array, $last_id_parent, $save_changes );
	
	$children_array = $tab_obj->cFindByParentID( $id_old );
	foreach ($children_array as $childname)
	{
		echo "<br>** migrando tab $childname.... **<br>";
		// se buscan los datos completos de la fila
		$child_values_array = $tab_obj->cFindByExpression( $childname, $id_old );
		// se guarda el id original de la tabla para buscar las pestañas
		$child_id_old    = $child_values_array['AD_TAB_ID'];

		$tab_obj->cMigrate( $child_values_array, $last_id_child, $id_old, $save_changes );

		$gchildren_array = $fld_obj->cFindByParentID( $child_id_old );
		foreach ($gchildren_array as $gcname)
		{
			echo "<br>** migrando field $gcname.... **<br>";
			// se buscan los datos completos de la fila
			$gchild_values_array = $fld_obj->cFindByExpression( $gcname, $child_id_old );
	
			$gchild_id_old = $gchild_values_array['AD_FIELD_ID'];
			//echo '<br/>'; print_r($fld_values_array); echo '<br/>';

			$tmp_obj->cPut( $gchild_id_old, $last_id_gchild, $gchild_values_array['NAME'], $fld_obj->getTablename( ) );

			$gchild_values_array['AD_FIELDGROUP_ID'] = 'NULL';
			
			// buscar el id correcto para la colomna del campo
			if( $gchild_values_array['AD_COLUMN_ID'] == 0 )
			{
				$gchild_values_array['AD_COLUMN_ID'] = 'NULL';
			}
			else
			{
				$colArrayAllData = $col_obj->cFindByPK( $gchild_values_array['AD_COLUMN_ID'] );
				$colname = strtoupper(substr( $colArrayAllData['COLUMNNAME'], 1, -1 )); //asumiendo que la funcion CFindByPK siempre devolverá un arreglo. (Ojo: Validar)
			 	if( $col_obj->cCountByExpression( $colname ) == 0 )
			 	{			 		
			 		$col_obj->cMigrate( $colname, $col_obj->cLastID()+1, $child_values_array['AD_TABLE_ID'], $save_changes );
			 		$gchild_values_array['AD_COLUMN_ID'] = $tmp_obj->cGetIDByOldID( 'AD_COLUMN', $gchild_values_array['AD_COLUMN_ID'] );
			 	}	
			 	else 
			 	{
			 		$table_id_s = $table_obj->cFindDPKBySPK( $child_values_array['AD_TABLE_ID'] );
			 		echo "<br/>$table_id_s<br/>";
			 		if ( $table_id_s != -1 )
			 		{
			 			$tmp_array  = $col_obj->cFindByExpression( $colname, $table_id_s, false );
			 			$gchild_values_array['AD_COLUMN_ID'] = $tmp_array['AD_COLUMN_ID'];
			 		}
			 	}
			}

			// buscar el id correcto para la referencia del campo
			if( $gchild_values_array['AD_REFERENCE_ID'] == 0 )
			{
				$gchild_values_array['AD_REFERENCE_ID'] = 'NULL';
			}
			else
			{
				$refArrayAllData = $ref_obj->cFindByPK( $gchild_values_array['AD_REFERENCE_ID'] );
				$refname = $refArrayAllData['NAME']; //asumiendo que la funcion CFindByPK siempre devolverá un arreglo. (Ojo: Validar)
			 	if($ref_obj->cCountByExpression( $refname ) == 0 )
			 	{
			 		$ref_obj->cMigrateByParentId( $child_values_array['AD_TABLE_ID'], $save_changes );
			 		$gchild_values_array['AD_REFERENCE_ID'] = $tmp_obj->cGetIDByOldID( 'AD_REFERENCE', $gchild_values_array['AD_REFERENCE_ID'] );
			 	}	
			 	else 
			 	{
			 		//$tmp_obj->cPut( , $refArrayAllData['AD_REFERENCE_ID'], $refname, 'AD_REFERENCE' );
			 		$gchild_values_array['AD_REFERENCE_ID'] = $refArrayAllData['AD_REFERENCE_ID'];
			 	}

			}
			
			$gchild_values_array['AD_TAB_ID']    = $last_id_child;
			$gchild_values_array['AD_FIELD_ID']    = $last_id_gchild;

			// se prepara consulta de migracion con id nuevo
			$fld_obj->cPut( $gchild_values_array, $save_changes );

			$last_id_gchild++;
		}

		$last_id_child++;
		
	} // end foreach

	$last_id_parent++;	
	
} // end foreach 

?>