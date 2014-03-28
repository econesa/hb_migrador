<?php 
//include '../utils.php';
include_once 'AdTab.php';

//session_start();

class AdField extends DataHandler
{
	const PARENT_TABLENAME  = 'AD_TAB'; // ojo falta 
	const TABLENAME         = 'AD_FIELD'; // ojo falta
	
	public function getTablename( )
	{
		return $this->tablename;
	}
	
	public function getExpression( )
	{
		return $this->expression;
	}

	public function __construct()
	{
		parent::load();
		$this->parent_tablename = 'AD_TAB';
		$this->tablename  = 'AD_FIELD';
		$this->expression = 'UPPER(T.NAME)';
	}

	public function cFindByParentID( $parent_id )
	{
		$result = array();
		
		$connection = oci_connect( $this->username_s, $this->password_s, $this->path_s );
		
		$query = " SELECT {$this->expression} 		
			  	   FROM   COMPIERE.{$this->tablename} t 
			  	   WHERE  {$this->parent_tablename}_ID = {$parent_id} ";
		echo "<br/> $query <br/>";

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

	function cFindByExpression( $value, $parentID )
	{
		$values_array = array();

		$query  = " SELECT * FROM $this->tablename t 
					WHERE $this->expression LIKE '$value' AND {$this->parent_tablename}_ID = $parentID ";
		echo "<br> $query <br>";

		try
		{
			$connection = oci_connect( $this->username_s, $this->password_s, $this->path_s );

			$tarray = listarTiposDeTabla( $connection, $this->tablename );		
			
			$stmt  = oci_parse( $connection, $query );
			if ( oci_execute( $stmt ) )
			{
				$values_array = oci_fetch_assoc( $stmt ); 
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
			
			oci_close($connection);	
		}
		catch( Exception $exc )
		{
			echo "Exception: $exc->getMessage() <br>";
		}

		return $values_array;
	}

	/*  */
	public function cMigrateByPK( $pk_id, $parent_id, $save_changes = true )
	{
		$entity_name = $this->cFindNameBySPK( $pk_id );
		$last_id_entity = $this->cMigrateByName( $entity_name, $this->cLastID() + 1, $parent_id, $save_changes );
		return $last_id_entity;	
	} // end cMigrateByPK

	/*  */
	public function cMigrateByName( $name, $last_id, $parent_id, $save_changes = true )
	{
		$entity_name = $name;
		$last_id_entity = $last_id;
		
		$ref_obj = new AdReference();
		
		echo "<br>$parent_id<br>";
		$values_array = $this->cFindByExpression( $entity_name, $parent_id );
	
		$gchild_id_old = $values_array['AD_FIELD_ID'];	

		// SE NECESITA obtener el ad_table_id de la pestaña ( o columna )
		$tab_obj = new AdTab();
		$tab_values_array = $tab_obj->cFindByPK( $parent_id );

		$col_obj = new AdColumn();
		$col_obj->cMigrateByPK( $values_array['AD_COLUMN_ID'],  $tab_values_array['AD_TABLE_ID'], $save_changes );

		$values_array['AD_FIELDGROUP_ID'] = 'NULL';

		echo "<br>** migrando field $entity_name.... **<br>";
		/*	
		$colArrayAllData = $this->cFindByPK(  );
		$colname = strtoupper(substr( $colArrayAllData['NAME'], 1, -1 )); //asumiendo que la funcion CFindByPK siempre devolverá un arreglo. (Ojo: Validar)
	 	if( $this->cCountByExpression( $colname ) == 0 )
	 	{			 		
	 		$col_obj->cMigrate( $colname, $values_array->cLastID() + 1, $child_values_array['AD_TABLE_ID'], $save_changes );
	 		$values_array['AD_COLUMN_ID'] = $tmp_obj->cGetIDByOldID( 'AD_COLUMN', $values_array['AD_COLUMN_ID'] );
	 	}
	 	else 
	 	{
	 		$table_id_s = $table_obj->cFindDPKBySPK( $child_values_array['AD_TABLE_ID'] );
	 		if ( $table_id_s != -1 )
	 		{
	 			$tmp_array  = $col_obj->cFindByExpression( $colname, $table_id_s, false );
	 			$values_array['AD_COLUMN_ID'] = $tmp_array['AD_COLUMN_ID'];
	 		}
	 	}
	 	*/

		// buscar el id correcto para la referencia del campo
		if( $values_array['AD_REFERENCE_ID'] == 0 )
		{
			$values_array['AD_REFERENCE_ID'] = 'NULL';
		}
		else
		{
		/*
			$refArrayAllData = $ref_obj->cFindByPK( $gchild_values_array['AD_REFERENCE_ID'] );
			$refname = $refArrayAllData['NAME']; //asumiendo que la funcion CFindByPK siempre devolverá un arreglo. (Ojo: Validar)
		 	if($ref_obj->cCountByExpression( $refname ) == 0 )
		 	{
		 		$ref_obj->cMigrateByParentId( $child_values_array['AD_TABLE_ID'], $save_changes );
		 		$values_array['AD_REFERENCE_ID'] = $tmp_obj->cGetIDByOldID( 'AD_REFERENCE', $values_array['AD_REFERENCE_ID'] );
		 	}	
		 	else 
		 	{
		 		//$tmp_obj->cPut( , $refArrayAllData['AD_REFERENCE_ID'], $refname, 'AD_REFERENCE' );
		 		$values_array['AD_REFERENCE_ID'] = $refArrayAllData['AD_REFERENCE_ID'];
		 	}
		*/
		}
			
		//$values_array['AD_TAB_ID']    = $last_id_child;
		$values_array['AD_FIELD_ID']  = $last_id_entity;

		// se prepara consulta de migracion con id nuevo
		$this->cPut( $values_array, $save_changes );

	}

} // end class

?>