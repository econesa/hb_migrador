<?php 
//include '../utils.php';
include_once 'AdProcess.php';
//session_start();

class AdProcessParam extends DataHandler
{
	
	public function getTablename( )
	{
		return $this->tablename;
	}

	/**/
	public function __construct()
	{
		parent::load();
		$this->parent_tablename  = 'AD_PROCESS';
		$this->tablename  = 'AD_PROCESS_PARA';
		$this->expression = 'UPPER(T.NAME)';
	}

	/**/
	public function cCount( $value, $parent_id )
	{
		if ( $this->connection != null )
			oci_close( $this->connection );

		$this->connection = oci_connect( $this->username_d, $this->password_d, $this->path_d );
		
		$rs_count = -1;
		$tarray = listarTiposDeTabla( $this->connection, $this->tablename );

		$query  = " SELECT COUNT(*) FROM $this->tablename t WHERE $this->expression LIKE '$value' AND {$this->parent_tablename}_ID = $parent_id ";
		//echo " <br> $query <br> ";

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
		
		return $rs_count;
	}

	public function cFindByParentID( $parent_id )
	{
		$result = array();
		$query  = " SELECT {$this->expression} 		
				    FROM   COMPIERE.{$this->tablename} t
				    WHERE  {$this->parent_tablename}_ID = {$parent_id} ";
		echo "<br> $this->tablename :: $query <br>";

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
			
			oci_close($connection);
		} 
		catch( Exception $exc )
		{
			echo "Exception: $exc->getMessage() <br>";
		}

		return $result;
	}

	function cFindByExpression( $value, $parent_id, $extern = true )
	{
		$values_array = array();

		if ( $extern )
		{
			$connection = oci_connect( $this->username_s, $this->password_s, $this->path_s );
		}
		else
		{
			$connection = oci_connect( $this->username_d, $this->password_d, $this->path_d );
		}

		$tarray = listarTiposDeTabla( $connection, $this->tablename );
		$query  = " SELECT * FROM $this->tablename t WHERE $this->expression LIKE '$value' AND t.{$this->parent_tablename}_ID = $parent_id ";
		echo "<br> $query <br>";
		
		$stmt = oci_parse( $connection, $query );
		if ( oci_execute( $stmt ) )
		{
			$values_array = oci_fetch_assoc( $stmt ); 
			$i = 0;

			foreach ( $values_array as $indice => $field )
			{
				if (empty($field))
				{
					$values_array[$indice] = formatEmpty( $tarray[$i]['tipo'], $field );
				}
				else
				{
					$values_array[$indice] = formatData( $tarray[$i]['tipo'], $field );
				}
				$i++;	
			}		

		} // execute 

		oci_close($connection);

		return $values_array;
	}

	public function cMigrateByPK( $pk_id, $parent_id, $save_changes = true )
	{
		$entity_name = $this->cFindNameBySPK( $pk_id );

		echo "<br> {$this->tablename} :: verificando proceso {$parent_id} debido al parametro $entity_name ... <br>";
		$parent_obj = new AdProcess();
		$new_parent_id = $parent_obj->cMigrateByPK( $parent_id, true );

		$last_id_entity = $this->cMigrateByName( $entity_name, $parent_id, $new_parent_id, $this->cLastID() + 1, $save_changes );
		return $last_id_entity;	
	} // end cMigrateByPK

	/**/
	public function cMigrateByName( $name, $old_parent_id, $parent_id, $last_id, $save_changes = true )
	{
		$entity_name = $name;
		$last_id_entity = $last_id;

		// verificar si ya esta en el origen, en cuyo caso se migra.
		$exists = $this->cCount( $entity_name, $parent_id ); 
		if ( $exists == 0 ) 
		{ 			
			$values_array = $this->cFindByExpression( $entity_name, $old_parent_id );
			
			if ( $values_array[ $this->tablename . '_ID' ] >= 5000000 )
			{				
				//echo "<br> AD_COLUMN:: verificando tabla {$old_parent_id} debido a columna $entity_name ... <br>";
				$values_array[ $this->tablename . '_ID' ] = $parent_id;
			
				echo "<br> migrando parametro del proceso $entity_name.... ($save_changes)<br>";
				$values_array[ $this->tablename . '_ID' ] = $last_id_entity;
				$this->cPut( $values_array, $save_changes );
			}
			else
			{
				echo ' el parametro del proceso es de compiere original';
			}
		}
		else
		{
			$values_array = $this->cFindByExpression( $entity_name, $parent_id, false );
			if ( !empty($values_array) )
			{
				$last_id_entity = $values_array[ $this->tablename . '_ID' ];
				echo "<br> ADProcessParam :: existe $entity_name con ID:$last_id_entity <br>";
			}
			else
				echo "<br> ADProcessParam :: existe $entity_name <br>";
		}

		return $last_id_entity;
	} // end cMigrateByName

	/* Migra una columna dado su nombre, el id de la tabla y el id que debe tener.
	**/
	public function cMigrate( $value_name, $column_id, $table_id, $save_changes = true )
	{
		// se buscan los datos completos de la fila
		$values_array = $this->cFindByExpression( $value_name, $table_id );
		
		
		$values_array['AD_CLIENT_ID'] = $values_array['AD_ORG_ID']  =  0;	// AD_Org_ID
		$col_id_old = $values_array[ $this->tablename . '_ID']; // guardar el id original
		
		$name = $this->cFindNameBySPK( $values_array[ strtoupper($this->tablename).'_ID' ] );
		
		$values_array[ $this->tablename . '_ID' ] = $column_id; // actualizo al ultimo id		
		
		// se prepara consulta de migracion con id nuevo
		$this->cPut( $values_array, $save_changes );	
	}

} // end class
?>