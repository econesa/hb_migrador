﻿<?php 
include_once '../../utils.php';
include_once 'DataHandler.php';

//session_start();

class AdElement extends DataHandler
{
	const TABLENAME  =  'AD_ELEMENT';

	public function __construct()
	{
		parent::load();
		$this->parent_tablename = 'AD_TABLE';
		$this->tablename  = 'AD_ELEMENT';
		$this->expression = 'UPPER(T.COLUMNNAME)'; // el indice asociativo esta dado en mayusculas
	}

	public function getTablename()
	{
		return $this->tablename;
	}
	
	public function cGet( $parent_id ) // ver si se utiliza aun 
	{
		$connection = oci_connect( $this->username_s, $this->password_s, $this->path_s );
		
		$mip   = explode( ".", $_SESSION['ip_destino'] );
		$dblinkname = 'HBE_DESA_' . $mip[3]; //Database Link
		
		$query = " SELECT UPPER(t.COLUMNNAME) 
				   FROM   COMPIERE.{$this->tablename} t 
				   JOIN   AD_COLUMN columna ON (columna.{$this->tablename}_ID = t.{$this->tablename}_ID) 
				   WHERE  columna.AD_TABLE_ID = $parent_id 
				     MINUS 
				   SELECT UPPER(t2.COLUMNNAME) 
				   FROM   COMPIERE.{$this->tablename}@{$dblinkname} t2 ";
		
		$stmt = oci_parse( $connection, $query );
		if ( oci_execute( $stmt ) )
		{
			$rs = oci_fetch_row($stmt);
		}
		
		//oci_free_statement($stmt);
		oci_close( $connection );
		
		return $rs;
	}

	/**/
	public function cFindByExpression( $value, $extern = true )
	{
		$values_array = array();
		$query  = " SELECT * FROM $this->tablename t WHERE $this->expression LIKE '$value' ";
		//echo "<br> $query <br/>";
		$connection   = null;

		try
		{
			if ( $extern )
			{
				$connection = oci_connect( $this->username_s, $this->password_s, $this->path_s );
			}
			else
			{
				$connection = oci_connect( $this->username_d, $this->password_d, $this->path_d );
			}

			$tarray = listarTiposDeTabla( $connection, $this->tablename );
			
			$stmt = oci_parse( $connection, $query );
			
			if ( oci_execute( $stmt ) )
			{
				$values_array = oci_fetch_assoc( $stmt ); 
				$i = 0;
				if ( !empty($values_array) )
				{
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
			} // execute 
			else
			{
				$e = oci_error( $stmt ); 
		        echo $e['message']; 
			}
	
			oci_close($connection);
		
		}
		catch( Exception $exc )
		{
			echo "Exception: $exc->getMessage() <br>";
		}

		return $values_array;
	}

	public function cMigrateByPK( $pk_id, $save_changes = true )
	{
		$entity_name    = $this->cFindNameBySPK( $pk_id );
		$last_id_entity = $this->cMigrateByName( $entity_name, $this->cLastID() + 1, $save_changes );
		return $last_id_entity;	
	} // end cMigrateByPK

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
			if ( $values_array['AD_ELEMENT_ID'] >= 5000000 )
			{				
				$values_array['AD_ELEMENT_ID'] = $last_id_entity;
				echo " elemento extendido - $last_id_entity <br/>";
				// se prepara consulta de migracion con id nuevo
				$values_array['AD_REFERENCE_ID'] = 'NULL'; 
				$values_array['AD_REFERENCE_VALUE_ID'] = 'NULL';
				$values_array['AD_VAL_RULE_ID'] = 'NULL'; 
				
				unset( $values_array[ 'AD_USER_ID' ] ); 
				$this->cPut( $values_array, $save_changes );
			}
			else
			{
				$values_array   = $this->cFindByExpression( $entity_name, false );
				$last_id_entity = $values_array['AD_ELEMENT_ID'];
				echo "<br> El elemento ya esta en compiere base - $entity_name con ID:$last_id_entity <br>";
			}
		}
		else
		{			
			$values_array = $this->cFindByExpression( $entity_name, false );
			$last_id_entity = $values_array['AD_ELEMENT_ID'];
			echo "<br> existe $entity_name con ID:$last_id_entity <br>";
		}	
		return $last_id_entity;

	} // end cMigrate

	public function cMigrateByParentId( $parent_id, $save_changes = true )
	{
		$id_old  = $parent_id;
		$tmp_obj = new TAdMig( $save_changes );

		$last_id_elem = $this->cLastID() + 1;
		$lista  = $this->cFindAllByParentId( $id_old );
		foreach ($lista as $elem_name)
		{
			$this->cMigrateByName( $elem_name, $save_changes ); 			
		}	
	} // end cMigrate


} // end class
?>