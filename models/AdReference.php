<?php 
include_once 'DataHandler.php';

//session_start();

class AdReference extends DataHandler
{
	const TABLENAME      = 'AD_REFERENCE';
	private $gparent_tablename = 'AD_TABLE';

	/**/
	public function getTablename( )
	{
		return self::TABLENAME;
	}

	public function load()
	{
		parent::load();
		$this->parent_tablename  = 'AD_COLUMN';
		$this->tablename  = 'AD_REFERENCE';
		$this->expression = 'UPPER(T.NAME)';
	}

	/**/
	public function getExpression( )
	{
		return $this->expression;
	}

	/**/
	public function cGet_Value( $gparent_id )
	{
		$data = array();
		$tablename  = self::TABLENAME;
		
		$connection = oci_connect( $this->username_s, $this->password_s, $this->path_s );

		$query = " SELECT UPPER(t.NAME)
				   FROM   {$tablename} t
				   JOIN   {$this->parent_tablename} parent ON (parent.AD_REFERENCE_VALUE_ID = t.AD_REFERENCE_ID)
				   WHERE  parent.{$this->gparent_tablename}_ID = $gparent_id ";
		
		echo " <br> $query <br> ";

		$stmt = oci_parse( $connection, $query );
		if ( oci_execute( $stmt ) )
		{
			$e = 0;			
			while (($row = oci_fetch_assoc($stmt)) != false) 
			{
			    $data[$e] = $row['UPPER(T.NAME)'];
			    $e++;
			}
		}
		else
		{
			$e = oci_error( $stmt ); 
	        echo $e['message']; 
		}
		//oci_free_statement($stmt);
		oci_close($connection);
		
		return $data;
	}

	/* busca en origen */
	public function cFindByExpression( $value, $extern = true )
	{
		$values_array = array();
		$connection = null;

		if ( $extern )
		{
			$connection = oci_connect( $this->username_s, $this->password_s, $this->path_s );
		}
		else
		{
			$connection = oci_connect( $this->username_d, $this->password_d, $this->path_d );
		}
				
		$tarray = listarTiposDeTabla( $connection, self::TABLENAME );

		$query  = " SELECT * FROM $this->tablename t WHERE $this->expression LIKE '$value' ";
		echo " <br> $query <br> ";

		$stmt = oci_parse( $connection, $query );
		if ( oci_execute( $stmt ) )
		{
			$values_array = oci_fetch_assoc( $stmt ); 
			if ( !empty($values_array) )
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
						$values_array[$indice] = formatData( $tarray[$i]['tipo'], $field  ); //$field;
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
	}
	
	/*
	public function cPut( $values_array, $save_changes = true )
	{
		$connection = oci_connect( $this->username_s, $this->password_s, $this->path_s );
		
		$insert_q = dameElInsertParcialDeLaTabla( $connection, $this->tablename );	
		
		oci_close($connection);	
		
		$c2    = oci_connect( $this->username_d, $this->password_d, $this->path_d );

		$query  = " INSERT into {$this->tablename} (AD_CLIENT_ID, AD_ORG_ID, AD_REFERENCE_ID, AD_TABLE_ID, COLUMN_DISPLAY_ID, COLUMN_KEY_ID,
					CREATED, CREATEDBY, ENTITYTYPE, ISACTIVE, ISDISPLAYIDENTIFIERS, ISVALUEDISPLAYED, ORDERBYCLAUSE, UPDATED, UPDATEDBY, 
					WHERECLAUSE, ISAPPROVED, DATEPROCESSED, SYNCHRONIZEDEFAULTS, SYSTEMSTATUS, AD_USER_ID) 
					VALUES  
					( :client_id, :org_id, :reference_id, :table_id, :column_display_id, :column_key_id, '{$values_array['CREATED']}', :createdby, ':ventitytype', 
					  '{$values_array['ISACTIVE']}',      '{$values_array['ISDISPLAYIDENTIFIERS']}', '{$values_array['ISVALUEDISPLAYED']}', 
					  '{$values_array['ORDERBYCLAUSE']}', '{$values_array['UPDATED']}', :updatedby,  '{$values_array['WHERECLAUSE']}', 
					  '{$values_array['ISAPPROVED']}', 	  '{$values_array['DATEPROCESSED']}',        '{$values_array['SYNCHRONIZEDEFAULTS']}', 
					  '{$values_array['SYSTEMSTATUS']}', :ad_user_id )";
		echo "<br> $query <br/>";
		
		$stmt = oci_parse( $c2, $query );

		oci_bind_by_name( $stmt, ':client_id', 	$values_array['AD_CLIENT_ID']);
		oci_bind_by_name( $stmt, ':org_id', 	$values_array['AD_ORG_ID']);
		oci_bind_by_name( $stmt, ':reference_id', $values_array['AD_REFERENCE_ID']);
		oci_bind_by_name( $stmt, ':table_id', 	$values_array['AD_TABLE_ID']);
		oci_bind_by_name( $stmt, ':column_display_id', $values_array['COLUMN_DISPLAY_ID']);
		oci_bind_by_name( $stmt, ':column_key_id', $values_array['COLUMN_KEY_ID']);
		//oci_bind_by_name( $stmt, ':dcreated', 	$values_array['CREATED']);
		oci_bind_by_name( $stmt, ':createdby', 	$values_array['CREATEDBY']);
		oci_bind_by_name( $stmt, ':ventitytype', $values_array['ENTITYTYPE']);
		//oci_bind_by_name( $stmt, ':visactive',	 $values_array['ISACTIVE']);
		//oci_bind_by_name( $stmt, ':isdisplayidentifiers', $values_array['ISDISPLAYIDENTIFIERS']);
		//oci_bind_by_name( $stmt, ':isvaluedisplayed', $values_array['ISVALUEDISPLAYED']);
		//oci_bind_by_name( $stmt, ':orderbyclause',	 $values_array['ORDERBYCLAUSE']);
		//oci_bind_by_name( $stmt, ':updated', 	$values_array['UPDATED']);
		oci_bind_by_name( $stmt, ':updatedby', 	$values_array['UPDATEDBY']);
		//oci_bind_by_name( $stmt, ':whereclause', $values_array['WHERECLAUSE']);
		//oci_bind_by_name( $stmt, ':isapproved', 	$values_array['ISAPPROVED']);
		//oci_bind_by_name( $stmt, ':dateprocessed', 	$values_array['DATEPROCESSED']);
		//oci_bind_by_name( $stmt, ':synchronizedefaults', $values_array['SYNCHRONIZEDEFAULTS']);
		//oci_bind_by_name( $stmt, ':systemstatus', 	$values_array['SYSTEMSTATUS']);
		oci_bind_by_name( $stmt, ':ad_user_id', 	$values_array['AD_USER_ID']);

		if ( $save_changes )
		{
			if ( oci_execute( $stmt ) )
			{
			}
			else
			{
				$e = oci_error($stmt); 
				echo $e['message'] . '<br/>'; 
			}	
		}

		oci_close($c2);		
	}
	*/

	/**/
	public function cPut( $values_array )
	{
		$connection = oci_connect( $this->username_s, $this->password_s, $this->path_s );
		
		$insert_q = dameElInsertParcialDeLaTabla( $connection, self::TABLENAME );

		oci_close($connection);

		$c2 = oci_connect( $this->username_d, $this->password_d, $this->path_d );

		$tablename = self::TABLENAME;	
		//
				
		// se prepara consulta de migracion con id nuevo
		$query = $insert_q . ' VALUES (' . implode(",", $values_array) . ')';
		echo "<br> $query <br/>";

		$stmt = oci_parse( $c2, $query );
		if ( oci_execute( $stmt ) )
		{
			echo "<br> insertado <br/>"; 
		}
		else
		{
			$e = oci_error( $stmt ); 
			echo $e['message'] . '<br/>'; 
		}	
		
		oci_close($c2);	
	}

	/* busca los datos de la hija de la referencia dado el parent_id y la clase de la hija*/
	public function findChildByRefId( $parent_id, $child_tablename )
	{
		$values_array  = array();
		
		$connection = oci_connect( $this->username_s, $this->password_s, $this->path_s );
		
		$tarray = listarTiposDeTabla( $connection, $child_tablename );

		$query = " SELECT  t.*
				   FROM    COMPIERE.{$child_tablename} t
				   WHERE   {$this->tablename}_ID = {$parent_id} ";
		echo "<br> $query <br>";

		$stmt = oci_parse( $connection, $query );

		if ( oci_execute( $stmt ) )
		{
			$values_array = oci_fetch_assoc( $stmt ); 
			echo '<br/>'; print_r( $values_array ); echo '<br/>'; 

			$i = 0;

			// parsear data para poder colocarla en el insertar
			foreach ( $values_array as $indice=>$field )
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

		} // execute 
		else
		{
			$e = oci_error( $stmt ); 
	        echo $e['message']; 
		}

		oci_close($connection);
		
		return $values_array;
	}

	//Funcion que migra los reference List y Table creados del origen al destino.
	public function migrateChildTable( $parent_id, $new_parent_id, $child_tablename, $save_changes = true )
	{
		$connection = oci_connect( $this->username_s, $this->password_s, $this->path_s );
		
		$insert_q = dameElInsertParcialDeLaTabla( $connection, $child_tablename );	
		
		oci_close($connection);	
		
		$c2 = oci_connect( $this->username_d, $this->password_d, $this->path_d );

		$values_array = $this->findChildByRefId( $parent_id, $child_tablename );
		$values_array[ strtoupper($this->tablename).'_ID' ] = $new_parent_id;

		// actualizar cada clave foranea 

		$name = $this->cFindNameBySPK( $values_array[ strtoupper($this->tablename).'_ID' ] );
		if ( !empty($name) )
		{
			echo "<br> Referencia $name <br>";
			echo '<br> fuente: ' . $values_array[strtoupper($this->tablename).'_ID'] . '<br>';		
			$id_new = $this->cFindPkByExpression( $name );
			if ( empty($id_new) )
			{
				// migrar

			}
			echo "<br> destino: $id_new <br>";
		}
		/**/

		$query = $insert_q . ' VALUES (' . implode(",", $values_array) . ')';
		echo "<br> $query <br/>";
		
		$stmt = oci_parse( $c2, $query );
		if ( $save_changes )
		{
			if ( oci_execute( $stmt ) )
			{
				echo '<br>Insertado<br>';
			}
			else
			{
				$e = oci_error($stmt); 
				echo $e['message'] . '<br/>'; 
			}	
		}

		oci_close($c2);
	}

	public function cMigrate( $values_array, $new_ref_id, $save_changes = true )
	{
		$id_old = $values_array['AD_REFERENCE_ID'];
		$tmp_obj = new TAdMig( $save_changes );

		$this->load();

		if ( $values_array['AD_REFERENCE_ID'] >= 5000000 )
		{
			echo " elemento extendido <br/>";
			$tmp_obj->cPut( $values_array['AD_REFERENCE_ID'], $new_ref_id, "{$values_array['NAME']}", self::TABLENAME );
			$old_ref_id = $values_array['AD_REFERENCE_ID'];
			$values_array['AD_REFERENCE_ID'] = $new_ref_id;
			$this->cPut( $values_array, $save_changes );
			
			echo ( $values_array['VALIDATIONTYPE'] );
			//Esto se refiere al migrar, en caso de la referencia tenga una validacion distinta al datatype se migra el elemento.
			switch ($values_array['VALIDATIONTYPE']) 
			{
				case "'L'":
					$this->migrateChildTable($old_ref_id, $new_ref_id, 'AD_REF_LIST');
					break;

				case "'T'":
					$this->migrateChildTable($old_ref_id, $new_ref_id, 'AD_REF_TABLE');
					break;

				case "'D'":
				default:	
					break;
			}			
		}
		else
		{
			$values_array = $this->cFindByExpression( $referencev_name, false );
			$refvo_values_array = $this->cFindByExpression( $referencev_name );
			if ( $values_array['AD_REFERENCE_ID'] >= 5000000 )
			{
				echo " elemento extendido <br/> ";
				$tmp_obj->cPut( $values_array['AD_REFERENCE_ID'], $values_array['AD_REFERENCE_ID'], $values_array['NAME'], self::TABLENAME );
			}
		}		 

	} // end cMigrate


	public function cMigrateByParentId( $parent_id, $save_changes = true )
	{
		$id_old  = $parent_id;
		$tmp_obj = new TAdMig( $save_changes );

		$this->load();

		$last_id_refv = $this->cLastID() + 1; 
		$refv_list    = $this->cGet_Value( $id_old ); // TODO: Actualizar el nombre de la función.
		foreach ($refv_list as $referencev_name) 
		{ 
			// verificar si el reference esta en el origen, en cuyo caso se migra.
			$refv_exists = $this->cCountByExpression( $referencev_name ); 
			echo " hay $refv_exists <br/>";
			if ( $refv_exists == 0 ) 
			{ 
				$values_array = $this->cFindByExpression( $referencev_name );
				if ( $values_array['AD_REFERENCE_ID'] >= 5000000 )
				{
					echo " elemento extendido <br/>";
					$tmp_obj->cPut( $values_array['AD_REFERENCE_ID'], $last_id_refv, $values_array['NAME'], self::TABLENAME );
					$values_array['AD_REFERENCE_ID'] = $last_id_refv;
					$this->cPut( $values_array, $save_changes );
					$last_id_refv++;
				}
			}
			else
			{
				$values_array = $this->cFindByExpression( $referencev_name, false );
				$refvo_values_array = $this->cFindByExpression( $referencev_name );
				if ( $values_array['AD_REFERENCE_ID'] >= 5000000 )
				{
					echo " elemento extendido <br/>";
					$tmp_obj->cPut( $refvo_values_array['AD_REFERENCE_ID'], $values_array['AD_REFERENCE_ID'], $values_array['NAME'], self::TABLENAME );
				}
			}
		} 

	} // end cMigrate

} // end class

?>