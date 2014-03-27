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
	
	public function __construct()
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

	/*  */
	public function cMigrateByPK( $pk_id, $save_changes = true )
	{
		$entity_name = $this->cFindNameBySPK( $pk_id );
		$last_id_entity = $this->cMigrateByName( $entity_name, $this->cLastID() + 1, $save_changes );
		return $last_id_entity;	
	} // end cMigrateByPK

	/*  */
	public function cMigrateByName( $name, $last_id, $save_changes = true )
	{
		$entity_name = $name;
		$last_id_entity = $last_id;

		// TODO
	}
	
	/* Migra una ventana dado su nombre y el id que debe tener.
	**/
	public function cMigrate( $values_array, $table_id, $parent_id, $save_changes = true )
	{
		$tmp_obj = new TAdMig( $save_changes );
		//$tmp_obj->truncate();

		$id_old    = $values_array['AD_TAB_ID']; // guardar el id original

		//
		$table_obj  = new AdTable();
		$values_array['AD_TABLE_ID'] = $table_obj->cMigrateByPK( $children_array['AD_TABLE_ID'], $last_id_child, $save_changes );
		
		echo "<br>** migrando pestaña {$values_array['NAME']}.... **<br>";
/*
		$seq_obj   = new AdSequence(); // TODO: verificar si la secuencia ya existe
		$seq_array = $seq_obj->cFindByTablename( $values_array['NAME'], $save_changes );
		$seq_array['AD_SEQUENCE_ID'] = $seq_obj->cLastID( ) + 1;
		$seq_obj->cPut( $seq_array, $save_changes );
*/
		
		$values_array['AD_TAB_ID']    = $table_id;
		
		// actualizar al id de la validacion dinamica del destino
		$win_obj = new AdWindow();
		
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