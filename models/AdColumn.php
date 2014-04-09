<?php 
//include '../utils.php';
include_once 'AdElement.php';
include_once 'AdReference.php';
//session_start();

class AdColumn extends DataHandler
{
	const PARENT_TABLENAME  = 'AD_TABLE';
	const TABLENAME    = 'AD_COLUMN';
	
	public function getTablename( )
	{
		return $this->tablename;
	}

	/**/
	public function __construct()
	{
		parent::load();
		$this->parent_tablename  = 'AD_TABLE';
		$this->tablename  = 'AD_COLUMN';
		$this->expression = 'UPPER(T.COLUMNNAME)';
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
		$query  = 
			" SELECT {$this->expression} 		
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
		$last_id_entity = $this->cMigrateByName( $entity_name, $parent_id, $this->cLastID() + 1, $save_changes );
		return $last_id_entity;	
	} // end cMigrateByPK

	/**/
	public function cMigrateByName( $name, $old_parent_id, $parent_id, $last_id, $save_changes = true )
	{
		$entity_name = $name;
		$last_id_entity = $last_id;

		/*
		if ( empty($parent_id) )
		{
			echo "<br> AD_Column_ID:: parent_id vacio <br>";
			exit;
		}
		else
			echo "<br/> Parent ID : $parent_id <br/>";
		*/
		
		// verificar si el reference esta en el origen, en cuyo caso se migra.
		$exists = $this->cCount( $entity_name, $parent_id ); 
		if ( $exists == 0 ) 
		{ 			
			$values_array = $this->cFindByExpression( $entity_name, $old_parent_id );
			
			if ( $values_array[ $this->tablename . '_ID' ] >= 5000000 )
			{
				$values_array['AD_PROCESS_ID'] = 'NULL';

				echo "<br> AD_COLUMN:: verificando elemento debido a columna $entity_name ... <br>";
				$elem_obj = new AdElement();
				$values_array['AD_ELEMENT_ID'] = $elem_obj->cMigrateByPK( $values_array['AD_ELEMENT_ID'], $save_changes );
				
				echo "<br> AD_COLUMN:: verificando referencia debido a columna $entity_name ... <br>";
				$ref_obj = new AdReference(); 
				if ( $values_array[ $ref_obj->getTablename() . '_VALUE_ID'] != 0 )
					$values_array[$ref_obj->getTablename() . '_VALUE_ID'] = $ref_obj->cMigrateByPK( $values_array[ $ref_obj->getTablename() . '_VALUE_ID'], $save_changes );
				else
					$values_array[ $ref_obj->getTablename() . '_VALUE_ID'] = 'NULL';

				echo "<br> AD_COLUMN:: verificando val rule debido a columna $entity_name ... <br>";
				$valrule_obj = new AdValRule(); 
				if ( $values_array[ $valrule_obj->getTablename() . '_ID'] != 0 )
					$values_array[ $valrule_obj->getTablename() . '_ID'] = $valrule_obj->cMigrateByPK( $values_array['AD_VAL_RULE_ID'], $save_changes );
				else
					$values_array[ $valrule_obj->getTablename() . '_ID'] = 'NULL';
				echo '<br> val rule id: ' . $values_array[ $valrule_obj->getTablename() . '_ID'] . '<br>';

				echo "<br> AD_COLUMN:: verificando tabla {$old_parent_id} debido a columna $entity_name ... <br>";
				$table_obj = new AdTable();
				$values_array[ 'AD_TABLE_ID' ] = $table_obj->cMigrateByPK( $old_parent_id, true );
			
				echo "<br> migrando columna $entity_name.... ($save_changes)<br>";
				$values_array[ $this->tablename . '_ID' ] = $last_id_entity;
				$this->cPut( $values_array, $save_changes );
			}
			else
			{
				echo ' la columna es de compiere original';
			}
		}
		else
		{
			// OJO parent id 
			$values_array = $this->cFindByExpression( $entity_name, $parent_id, false );
			if ( !empty($values_array) )
			{
				$last_id_entity = $values_array[ $this->tablename . '_ID' ];
				echo "<br> AD_COLUMN :: existe $entity_name con ID:$last_id_entity <br>";
			}
			else
				echo "<br> AD_COLUMN :: existe $entity_name <br>";
		}

		return $last_id_entity;
	} // end cMigrateByName

	/* Migra una columna dado su nombre, el id de la tabla y el id que debe tener.
	**/
	public function cMigrate( $value_name, $column_id, $table_id, $save_changes = true )
	{
		// se buscan los datos completos de la fila
		$values_array = $this->cFindByExpression( $value_name, $table_id );
		
		// guardar en tabla temporal	
		$tmp_obj  = new TAdMig();
		
		$elem_obj = new AdElement();
		$ref_obj  = new AdReference();
		$vr_obj   = new AdValRule();
		$tabla_obj = new AdTable();

		$values_array['AD_CLIENT_ID'] = 0; 
		$values_array['AD_ORG_ID'] = 0;	// AD_Org_ID
		$values_array['VERSION']   = 1;	// Version
		$col_id_old = $values_array['AD_COLUMN_ID']; // guardar el id original
		$tmp_obj->cPut( $col_id_old, $column_id, $values_array['COLUMNNAME'], self::TABLENAME );

		//buscar el id table que le corresponde dado el viejo
		$tableid = $values_array['AD_TABLE_ID']; // AD_Table_ID
		$refid   = $values_array['AD_REFERENCE_ID']; // AD_Reference_ID
		$ref_value_id = $values_array['AD_REFERENCE_VALUE_ID']; // AD_Reference_ID
		$vruleid      = $values_array['AD_VAL_RULE_ID']; // AD_Val_Rule_ID
		
		
		$name = $this->cFindNameBySPK( $values_array[ strtoupper($this->tablename).'_ID' ] );

		// actualizar al id del elemento del destino
		$elem_name  = $elem_obj->cFindNameBySPK( $values_array['AD_ELEMENT_ID'] );
		$new_elemid = $elem_obj->cFindPkByExpression( $elem_name );
		if ( $new_elemid != -1 )
			 $values_array[$elem_obj->getTablename() . '_ID'] = $new_elemid;
		echo "<br/>$elem_name: $new_elemid <br/>";

		// actualizar al id de la referencia del destino
		$ref_name  = $ref_obj->cFindNameBySPK( $values_array[$ref_obj->getTablename() . '_ID'] );
		$new_refid = $ref_obj->cFindPkByExpression( $ref_name );
		if ( $new_refid != -1 )
			 $values_array[$ref_obj->getTablename() . '_ID'] = $new_refid;
		echo "<br/> $ref_name: $new_refid <br/>";

		// actualizar al id de la referencia valor del destino
		$ref_val_name  = $ref_obj->cFindNameBySPK( $values_array[$ref_obj->getTablename() . '_VALUE_ID'] );
		if ( !empty($ref_val_name) )
		{
			$new_refvalueid = $ref_obj->cFindPkByExpression( $ref_val_name );
			if ( $new_refvalueid != -1 )
				 $values_array[$ref_obj->getTablename() . '_VALUE_ID'] = $new_refvalueid;
			echo "<br/> $ref_val_name: $new_refvalueid <br/>";
		}
		else
			 $values_array[$ref_obj->getTablename() . '_VALUE_ID'] = 'NULL';
		
		$values_array['AD_PROCESS_ID'] = 'NULL';
		
		// actualizar al id de la validacion dinamica del destino
		$vrule_name  = $vr_obj->cFindNameBySPK( $values_array[$vr_obj->getTablename() . '_ID'] );
		if ( !empty($vrule_name) )
		{
			$new_vruleid = $vr_obj->cFindPkByExpression( $vrule_name );
			if ( $new_vruleid != -1 )
				 $values_array[$vr_obj->getTablename() . '_ID'] = $new_vruleid;
			echo "<br/> $vrule_name: $new_vruleid <br/>";
		}
		else
			 $values_array[$vr_obj->getTablename() . '_ID'] = 'NULL';


		// actualizar al id de la tabla del destino
		$table_name  = $tabla_obj->cFindNameBySPK( $values_array[$tabla_obj->getTablename() . '_ID'] );
		if ( !empty($table_name) )
		{
			$new_tableid = $tabla_obj->cFindPkByExpression( $table_name );
			if ( $new_tableid != -1 )
				 $values_array[$tabla_obj->getTablename() . '_ID'] = $new_tableid;
			echo "<br/> $table_name: $new_tableid <br/>";
		}
		else
			 $values_array[$tabla_obj->getTablename() . '_ID'] = 'NULL';
		
		$values_array['AD_PROCESS_ID'] = 'NULL';

		// se prepara consulta de migracion con id nuevo
		$values_array['AD_COLUMN_ID'] = $column_id; // actualizo al ultimo id		
		
		// se prepara consulta de migracion con id nuevo
		$this->cPut( $values_array, $save_changes );	
	}

} // end class
?>