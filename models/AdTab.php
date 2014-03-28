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

	/**/
	public function cCount( $value, $parent_id )
	{
		$rs_count = -1;
		$query  = " SELECT COUNT(*) FROM $this->tablename t WHERE $this->expression LIKE '$value' AND {$this->parent_tablename}_ID = $parent_id ";
		//echo " <br> $query <br> ";

		if ( $this->connection != null )
			oci_close( $this->connection );

		try
		{
			$this->connection = oci_connect( $this->username_d, $this->password_d, $this->path_d );
		
			$tarray = listarTiposDeTabla( $this->connection, $this->tablename );		

			$stmt = oci_parse( $this->connection, $query );
			if ( oci_execute( $stmt ) )
			{
				$rs_count = oci_fetch_assoc( $stmt );
				$rs_count = $rs_count['COUNT(*)'];
			}
			else
			{
				$e = oci_error( $stmt ); 
		        echo $e['message']; 
			}
			
			oci_close( $this->connection );
		}
		catch( Exception $exc )
		{
			echo "Exception: $exc->getMessage() <br>";
		}
		
		return $rs_count;
	} // end cCount

	/**/
	public function cFindByParentID( $parent_id )
	{
		$result  = array();
		$query   = 
			" SELECT {$this->expression} 		
			  FROM   COMPIERE.{$this->tablename} t
			  WHERE  {$this->parent_tablename}_ID = {$parent_id} ";
		//echo "<br> $query <br>";

		try
		{
			$connection = oci_connect( $this->username_s, $this->password_s, $this->path_s );

			$stmt = oci_parse( $connection, $query );
			if ( oci_execute( $stmt ) )
			{
				$nrows  = oci_fetch_all($stmt, $res);
				$result = $res[$this->expression];
			}
			else
			{ 
				$e = oci_error($stmt); 
				echo $e['message'] . '<br/>'; 
			}
			
			oci_close( $connection );
		}
		catch( Exception $exc )
		{
			echo "Exception: $exc->getMessage() <br>";
		}
		
		return $result;
	} // end cFindByParentID

	/**/
	function cFindByExpression( $value, $parentID, $extern = true )
	{
		$values_array = array();
		$query = " SELECT * FROM $this->tablename t WHERE $this->expression LIKE '$value' AND {$this->parent_tablename}_ID = $parentID ";
		//echo "<br> $query <br>";

		if ( $extern )
		{
			$connection = oci_connect( $this->username_s, $this->password_s, $this->path_s );
		}
		else
		{
			$connection = oci_connect( $this->username_d, $this->password_d, $this->path_d );
		}

		$tarray = listarTiposDeTabla( $connection, $this->tablename );
				
		$stmt  = oci_parse( $connection, $query );
		
		if ( oci_execute( $stmt ) )
		{
			$values_array = oci_fetch_assoc( $stmt ); 
			if (!empty($values_array))
			{
				$i = 0;
				foreach ( $values_array as $indice => $field )
				{
					if ( empty($field) )
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
		else
		{
			$e = oci_error( $stmt ); 
	        echo $e['message']; 
		}

		oci_close($connection);

		return $values_array;
	} // end cFindByExpression

	/*  */
	public function cMigrateByPK( $pk_id, $save_changes = true )
	{
		$entity_name = $this->cFindNameBySPK( $pk_id );
		$last_id_entity = $this->cMigrateByName( $entity_name, $this->cLastID() + 1, $save_changes );
		return $last_id_entity;	
	} // end cMigrateByPK

	/*  */
	public function cMigrateByName( $name, $last_id_tab, $old_win_id, $save_changes = true )
	{
		$entity_name = $name;
		$last_id_entity = $last_id_tab			
		$exists = $this->cCount( $entity_name, $old_win_id ); 
		
		if ($exists == 0) 
		{
			$values_array = $this->cFindByExpression( $entity_name, $old_win_id, true ); //

			$table_obj  = new AdTable();
			$values_array['AD_TABLE_ID'] = $table_obj->cMigrateByName( $table_asoc, $last_id_child, $save_changes );	
			
			if ( $values_array['AD_TAB_ID'] >= 5000000 )
			{
				$values_array['AD_TAB_ID'] = $last_id_entity;
				
				echo "<br> migrando tab (pestaña) $entity_name.... <br>";

				echo '<br> Datos de Pestaña: '; print_r($values_array); echo '<br>';

				$this->cPut( $values_array, $save_changes );
			}
			else
			{
				echo "<br/> El Tab ya esta en el compiere original. <br/>";
			}			
		}
		else
		{
			echo "<br/> La pestaña (tab) $entity_name ya existe en el destino <br/>";
		}

		return $last_id_entity;

	} // end cMigrateByName
	
	/* Migra una ventana dado su nombre y el id que debe tener.
	**/
	public function cMigrate( $values_array, $table_id, $parent_id, $save_changes = true )
	{
		$tmp_obj = new TAdMig( $save_changes );
		//$tmp_obj->truncate();

		$id_old    = $values_array['AD_TAB_ID']; // guardar el id original

		//
		$table_obj  = new AdTable();
		$values_array['AD_TABLE_ID'] = $table_obj->cMigrateByPK( $values_array['AD_TABLE_ID'], $save_changes );
		
		echo "<br>** migrando pestaña {$values_array['NAME']}.... **<br>";
/*
		$seq_obj   = new AdSequence(); // TODO: verificar si la secuencia ya existe
		$seq_array = $seq_obj->cFindByTablename( $values_array['NAME'], $save_changes );
		$seq_array['AD_SEQUENCE_ID'] = $seq_obj->cLastID( ) + 1;
		$seq_obj->cPut( $seq_array, $save_changes );
*/
		
		$values_array['AD_TAB_ID'] = $table_id;
		
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