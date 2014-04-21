<?php 
include_once '../../utils.php';
include_once 'DataHandler.php';

//session_start();

class AdReference extends DataHandler
{
	const TABLENAME      = 'AD_REFERENCE';
	private $gparent_tablename = 'AD_TABLE';

	/**/
	public function getTablename( )
	{
		return $this->tablename;
	}

	/**/
	public function __construct()
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
		//echo " <br> $query <br> ";

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
			//echo '<br/>'; print_r( $values_array ); echo '<br/>'; 

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

	public function cMigrateByPK( $pk_id, $save_changes = true )
	{
		$entity_name = $this->cFindNameBySPK( $pk_id );
		$last_id_entity = $this->cMigrateByName( $entity_name, $this->cLastID() + 1, $save_changes );
		return $last_id_entity;	
	} // end cMigrateByPK

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

	/**/
	public function cMigrateByName( $name, $last_id, $save_changes = true )
	{
		$entity_name = $name;
		$last_id_entity = $last_id;

		echo "<br> {$this->tablename} :: migrando referencia $entity_name.... <br>";

		$exists = $this->cCountByExpression( $entity_name ); 
		
		if ($exists == 0) 
		{
			// se buscan los datos completos de la fila
			$values_array = $this->cFindByExpression( $entity_name );

			if ( $values_array['AD_REFERENCE_ID'] >= 5000000 )
			{
				echo " referencia extendida <br/>";
				
				$old_ref_id = $values_array['AD_REFERENCE_ID'];
				$values_array['AD_REFERENCE_ID'] = $last_id_entity;
				$this->cPut( $values_array, $save_changes );
			
				//Esto se refiere al migrar, en caso de la referencia tenga una validacion distinta al datatype se migra el elemento.
				switch ( $values_array['VALIDATIONTYPE'] ) 
				{
					case "'L'":
						$this->migrateChildTable($old_ref_id, $values_array['AD_REFERENCE_ID'], 'AD_REF_LIST');
						break;

					case "'T'":
						$this->migrateChildTable($old_ref_id, $values_array['AD_REFERENCE_ID'], 'AD_REF_TABLE');
						break;

					case "'D'":
					default:	
						break;
				}
			}
			else
			{
				$values_array = $this->cFindByExpression( $name, false );
				$last_id_entity = $values_array['AD_REFERENCE_ID'];
				echo "<br> La referencia ya esta en compiere original - $name con ID:$last_id_entity <br>";		
			}
		}
		else
		{
			$values_array = $this->cFindByExpression( $entity_name, false );
			$last_id_entity = $values_array['AD_REFERENCE_ID'];
			echo "<br> existe $entity_name con ID:$last_id_entity <br>";
		}	

		return $last_id_entity;
	} // end cMigrateByName

/*
	public function cMigrate( $values_array, $new_ref_id, $save_changes = true )
	{
		$id_old = $values_array['AD_REFERENCE_ID'];
		$tmp_obj = new TAdMig( $save_changes );

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
*/

	public function cMigrateByParentId( $parent_id, $save_changes = true )
	{
		$id_old  = $parent_id;
		$tmp_obj = new TAdMig( $save_changes );

		$last_id_refv = $this->cLastID() + 1; 
		$refv_list    = $this->cGet_Value( $id_old ); // TODO: Actualizar el nombre de la función.
		foreach ($refv_list as $referencev_name) 
		{ 
			echo "<br>** migrando referencia $referencev_name.... **<br>";
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