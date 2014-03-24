<?php 
//include '../utils.php';

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
	public function load()
	{
		parent::load();
		$this->parent_tablename  = 'AD_TABLE';
		$this->tablename  = 'AD_COLUMN';
		$this->expression = 'UPPER(T.COLUMNNAME)';
	}

	public function cFindByParentID( $parent_id )
	{
		$result = array();
		
		$mip = explode(".", $_SESSION['ip_destino'] );
		$enlace_nombre = 'HBE_DESA_' . $mip[3]; //Database Link

		$username   = $_SESSION['user_origen'];
		$password   = $_SESSION['user_opw'];
		$connection = oci_connect( $username, $password, $_SESSION['ip_origen'] . '/XE' );
		
		$query = 
			" SELECT {$this->expression} 		
			  FROM   COMPIERE.{$this->tablename} t
			  WHERE  {$this->parent_tablename}_ID = {$parent_id} ";

		echo "<br>$query<br>";

		$stmt = oci_parse( $connection, $query );
		if ( oci_execute( $stmt ) )
		{}
		else{ 
			$e = oci_error($stmt); 
			echo $e['message'] . '<br/>'; 
		}
		$nrows = oci_fetch_all($stmt, $res);

		oci_close($connection);
		
		return $res[$this->expression];
	}

	function cFindByExpression( $value, $parentID, $extern = true )
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
		$query  = " SELECT * FROM $this->tablename t WHERE $this->expression LIKE '$value' AND t.{$this->parent_tablename}_ID = $parentID ";
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
	
	public function cPut( $values_array, $save_changes = true )
	{
		$username   = $_SESSION['user_destino'];
		$password   = $_SESSION['user_dpw'];
		$connection = oci_connect( $username, $password, $_SESSION['ip_destino'] . '/XE' );
		
		$insert_q = dameElInsertParcialDeLaTabla( $connection, $this->tablename );		
		$query = $insert_q . ' VALUES (' . implode(",", $values_array) . ')';
		echo "<br>$query<br>";

		$stmt = oci_parse( $connection, $query );
		if ( $save_changes && oci_execute( $stmt ) )
		{
			echo "<br>insertado<br>";
		}
		else{ 
			$e = oci_error($stmt); 
			echo $e['message'] . '<br/>'; 
		}
		oci_close( $connection );
	}

	/* Migra una columna dado su nombre, el id de la tabla y el id que debe tener.
	**/
	public function cMigrate( $value_name, $column_id, $table_id, $save_changes = true )
	{
		// se buscan los datos completos de la fila
		$values_array = $this->cFindByExpression( $value_name, $table_id );
		
		// guardar en tabla temporal	
		$tmp_obj  = new TAdMig();
		
		$elem_obj = new AdElement();
		$elem_obj->load();

		$ref_obj = new AdReference();
		$ref_obj->load();

		$vr_obj = new AdValRule();
		$vr_obj->load();

		$tabla_obj = new AdTable();
		$tabla_obj->load();

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