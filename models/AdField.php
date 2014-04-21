<?php 
//include '../utils.php';
include_once 'AdTab.php';
include_once 'AdColumn.php';

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

		$query = " SELECT {$this->expression} 		
			  	   FROM   COMPIERE.{$this->tablename} t 
			  	   WHERE  {$this->parent_tablename}_ID = {$parent_id} ";
		//echo "<br/> $query <br/>";
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
	} // cFindByParentID

	function cFindByExpression( $value, $parentID )
	{
		$values_array = array();

		$query  = " SELECT * FROM $this->tablename t 
					WHERE $this->expression LIKE '$value' AND {$this->parent_tablename}_ID = $parentID ";
		//echo "<br> $query <br>";

		try
		{
			$connection = oci_connect( $this->username_s, $this->password_s, $this->path_s );

			$tarray = listarTiposDeTabla( $connection, $this->tablename );		
			
			$stmt = oci_parse( $connection, $query );
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
	} // cFindByExpression

	/*  */
	public function cMigrateByPK( $pk_id, $parent_id, $save_changes = true )
	{
		$entity_name = $this->cFindNameBySPK( $pk_id );
		$last_id_entity = $this->cMigrateByName( $entity_name, $this->cLastID() + 1, $parent_id, $save_changes );
		return $last_id_entity;	
	} // end cMigrateByPK

	/*  */
	public function cMigrateByName( $name, $last_id, $parent_id, $new_parent_id, $save_changes = true )
	{
		$entity_name = $name;
		$last_id_entity = $last_id;
		
		$ref_obj = new AdReference();
		$tab_obj = new AdTab();
		$col_obj = new AdColumn();

		//P1
		$values_array = $this->cFindByExpression( $entity_name, $parent_id );
	
		$gchild_id_old = $values_array['AD_FIELD_ID'];	

		//P2 
		$values_array['AD_TAB_ID'] = $new_parent_id;
		
		$tab_values_array = $tab_obj->cFindByPK( $parent_id );

		echo "<br> AD_FIELD:: verificando columna {$values_array['AD_COLUMN_ID']} debido al campo $entity_name ... <br>";

		$values_array['AD_COLUMN_ID'] = $col_obj->cMigrateByPK( $values_array['AD_COLUMN_ID'], $tab_values_array['AD_TABLE_ID'], $save_changes );

		$values_array['AD_FIELDGROUP_ID'] = 'NULL';

		echo "<br> AD_FIELD:: verificando referencia {$values_array['AD_COLUMN_ID']} debido a campo $entity_name ... <br>";
		if ( $values_array[ $ref_obj->getTablename() . '_ID'] != 0 )
			$values_array[$ref_obj->getTablename() . '_ID'] = $ref_obj->cMigrateByPK( $values_array[ $ref_obj->getTablename() . '_VALUE_ID'], $save_changes );
		else
			$values_array[ $ref_obj->getTablename() . '_ID'] = 'NULL';
		
		//P3	
		$values_array['AD_FIELD_ID']  = $last_id_entity;
		
		//P4
		// se prepara consulta de migracion con id nuevo
		echo "<br> migrando field $entity_name ... <br>";
		$this->cPut( $values_array, $save_changes );

		return $last_id_entity;

	} // end cMigrateByName

} // end class

?>