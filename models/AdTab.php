<?php 
//include '../utils.php';
include_once 'DataHandler.php';

//session_start();

class AdTab extends DataHandler
{
	const TABLENAME         = 'AD_TAB';
	const PARENT_TABLENAME  = 'AD_WINDOW';
	
	public function getTablename( )
	{
		return $this->tablename;
	}
	
	public function load()
	{
		parent::load();
		$this->parent_tablename  = 'AD_WINDOW'; // ojo quizas falta 
		$this->tablename  = 'AD_TAB';
		$this->expression = 'UPPER(T.NAME)';
	}

	public function cFindByParentID( $parent_id )
	{
		$result     = array();
		
		$mip = explode(".", $_SESSION['ip_destino'] );
		$enlace_nombre = 'HBE_DESA_' . $mip[3]; //Database Link

		$username   = $_SESSION['user_origen'];
		$password   = $_SESSION['user_opw'];
		$connection = oci_connect( $username, $password, $_SESSION['ip_origen'] . '/XE' );
		
		$query = 
			" SELECT {$this->expression} 		
			  FROM   COMPIERE.{$this->tablename} t
			  WHERE  {$this->parent_tablename}_ID = {$parent_id} ";
		//echo "<br> $query <br>";

		$stmt = oci_parse( $connection, $query );
		if ( oci_execute( $stmt ) )
		{}
		else{ 
			$e = oci_error($stmt); 
			echo $e['message'] . '<br/>'; 
		}
		$nrows = oci_fetch_all($stmt, $res);

		oci_close( $connection );
		
		return $res[$this->expression];
	}

	function cFindByExpression( $value, $parentID )
	{
		$values_array = array();

		$username   = $_SESSION['user_origen'];
		$password   = $_SESSION['user_opw'];
		$connection = oci_connect( $username, $password, $_SESSION['ip_origen'] . '/XE' );

		$tarray = listarTiposDeTabla( $connection, $this->tablename );
		//$column_value = $value; // deberia sanitizarse
		//print_r( $tarray ); 
		$query = " SELECT * FROM $this->tablename t WHERE $this->expression LIKE '$value' AND {$this->parent_tablename}_ID = $parentID ";
		//echo "<br> $query <br>";
		
		$stmt  = oci_parse( $connection, $query );
		
		if ( oci_execute( $stmt ) )
		{
			$values_array = oci_fetch_assoc( $stmt ); 
			if (!empty($values_array))
			{
				$i = 0;
				foreach ( $values_array as $indice => $field )
				{
					if ( empty($field) && $field != 0 )
					{
						$values_array[$indice] = formatEmpty( $tarray[$i]['tipo'], $field );
					}
					else
					{
						$values_array[$indice] = formatData( $tarray[$i]['tipo'], $field );
					}				
					$i++;
				}
			}			
		}

		oci_close($connection);

		return $values_array;
	}
	
	/* Migra una ventana dado su nombre y el id que debe tener.
	**/
	public function cMigrate( $values_array, $table_id, $parent_id, $save_changes = true )
	{
		$tmp_obj = new TAdMig( $save_changes );
		//$tmp_obj->truncate();
		
		$this->load();

		$id_old    = $values_array['AD_TAB_ID']; // guardar el id original

		//
		$table_obj  = new AdTable();
		$table_obj->load();
		$last_id_child = $table_obj->cLastID() + 1;
		$table_values_array = $table_obj->cFindAllByParentId( $values_array['AD_TAB_ID'] );
		foreach ($table_values_array as $childname)
		{
			echo "<br>** migrando tabla $childname.... **<br>";
			$children_array = $table_obj->cFindByExpression( $childname );
			$adtablename = strtoupper(substr( $children_array['TABLENAME'], 1, -1 ));
			$tbl_count = $table_obj->cCountByExpression( $adtablename );
			echo "count(*) = $tbl_count => No se encuentra en el destino <br/>";
			if ( $tbl_count == 0)
			{
				$table_obj->cMigrate( $children_array, $last_id_child, $save_changes );
				$last_id_child++;
			}
			else
			{
				$tmp_obj->cPut( $children_array['AD_TABLE_ID'], $table_obj->cFindByExpression( $childname ), "'$adtablename'", 'AD_TABLE' );
			}

		} // end foreach

		
		echo "<br>** migrando pestaña {$values_array['NAME']}.... **<br>";
/*
		$seq_obj   = new AdSequence(); // TODO: verificar si la secuencia ya existe
		$seq_array = $seq_obj->cFindByTablename( $values_array['NAME'], $save_changes );
		$seq_array['AD_SEQUENCE_ID'] = $seq_obj->cLastID( ) + 1;
		$seq_obj->cPut( $seq_array, $save_changes );
*/
		//$tmp_obj->cPut( $id_old, $table_id, $values_array['NAME'], $this->tablename );

		$values_array['AD_TAB_ID']    = $table_id;

		//verifica que el nombre de la tabla exista en el destino y buscar el valor del id.
		$new_table_id = $table_obj->cFindDPKBySPK( $values_array['AD_TABLE_ID'] );
		if ( strcmp($new_table_id, 'NULL') != 0 ) 
			$values_array['AD_TABLE_ID']  = $new_table_id;
		else
		{
			// como es un campo obligatorio que devuelva NULL significa que no ha sido migrada la tabla aun
			/*
			$table_name   = $table_obj->cFindNameBySPK( $values_array['AD_TABLE_ID'] );
			$table_values_array = $table_obj->cFindByExpression( $table_name );
	
			$table_obj->cMigrate( $table_values_array, $last_id_child, $save_changes );
			$values_array['AD_TABLE_ID'] = $last_id_child;
			*/
		}
		
		// actualizar al id de la validacion dinamica del destino
		$win_obj = new AdWindow();
		$win_obj->load();
		$win_name  = $win_obj->cFindNameBySPK( $values_array[$win_obj->getTablename() . '_ID'] );
		if ( !empty($win_name) )
		{
			$new_win_id = $win_obj->cFindPkByExpression( $win_name );
			if ( $new_win_id != -1 )
				 $values_array[$win_obj->getTablename() . '_ID'] = $new_win_id;
			echo "<br/> $win_name: $new_win_id <br/>";
		}
		else
			 $values_array[$win_obj->getTablename() . '_ID'] = 'NULL';

		// se prepara consulta de migracion con id nuevo
		$values_array['AD_CLIENT_ID'] = $values_array['AD_ORG_ID'] = $values_array['TABLEVEL'] = 0;
		$values_array['AD_COLUMN_ID'] = $values_array['AD_COLUMNSORTORDER_ID'] = $values_array['AD_COLUMNSORTYESNO_ID'] = 
			$values_array['AD_CTXAREA_ID']   = $values_array['AD_IMAGE_ID'] = $values_array['AD_PROCESS_ID'] = 
			$values_array['INCLUDED_TAB_ID'] = $values_array['REFERENCED_TAB_ID'] = 'NULL'; 
		//echo '<br/>'; print_r( $values_array ); echo '<br/>';

		$this->cPut( $values_array, $save_changes ); 
		//$this->cPut( $this->prepareValues($values_array));		
	}

} // end class
?>