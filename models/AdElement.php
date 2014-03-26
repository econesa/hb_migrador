﻿<?php 
include_once 'DataHandler.php';

//session_start();

class AdElement extends DataHandler
{
	const TABLENAME  =  'AD_ELEMENT';

	public function load()
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
		$username   = $_SESSION['user_origen'];
		$password   = $_SESSION['user_opw'];
		$connection = oci_connect( $username, $password, $_SESSION['ip_origen'] . '/XE' );
		
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
		$tablename    = self::TABLENAME;
		$connection   = null;

		if ( $extern )
		{
			$connection = oci_connect( $this->username_s, $this->password_s, $this->path_s );
		}
		else
		{
			$connection = oci_connect( $this->username_d, $this->password_d, $this->path_d );
		}

		$query  = " SELECT * FROM $tablename t WHERE $this->expression LIKE '$value' ";
		//echo "<br> $query <br/>";

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
		
		return $values_array;
	}
	
	public function cPut( $values_array )
	{
		//$last_id_table = $this->cLastID() + 1;

		$username1  = $_SESSION['user_origen'];
		$password1  = $_SESSION['user_opw'];
		$connection = oci_connect( $username1, $password1, $_SESSION['ip_origen'] . '/XE' );
		
		$username2  = $_SESSION['user_destino'];
		$password2  = $_SESSION['user_dpw'];
		$c2 		= oci_connect( $username2, $password2, $_SESSION['ip_destino'] . '/XE' );
		
		$mip = explode( ".", $_SESSION['ip_destino'] );
		$enlace_nombre = 'HBE_DESA_' . $mip[3]; //Database Link

		$tablename = self::TABLENAME;	
		//
		$insert_q = dameElInsertParcialDeLaTabla( $connection, self::TABLENAME );
		
		// se prepara consulta de migracion con id nuevo
		$values_array['AD_REFERENCE_ID'] = 'NULL'; 
		$values_array['AD_REFERENCE_VALUE_ID'] = 'NULL';
		$values_array['AD_VAL_RULE_ID'] = 'NULL'; 
		//$values_array['AD_ELEMENT_ID']  = $last_id_table; // actualizo al ultimo id
		$query = $insert_q . ' VALUES (' . implode(",", $values_array) . ')';
		echo "<br> $query <br/>";

		$stmt4 = oci_parse( $c2, $query );
		if ( oci_execute( $stmt4 ) )
		{
			echo "<br> insertado <br/>"; 
		}
		else
		{
			$e = oci_error($stmt4); 
			echo $e['message'] . '<br/>'; 
		}	

		oci_close($connection);
		oci_close($c2);	
	}

	public function cMigrateByName( $name, $save_changes = true )
	{
		$elem_name = $name;	
		echo "<br> migrando elemento $elem_name.... <br>";
		$elem_exists = $this->cCountByExpression( $elem_name ); 
		
		if ($elem_exists == 0) 
		{
			// se buscan los datos completos de la fila
			$elem_values_array = $this->cFindByExpression( $elem_name );
			if ( $elem_values_array['AD_ELEMENT_ID'] >= 5000000 )
			{
				echo " elemento extendido <br/>";
				// se guarda ids en tabla temporal
				//$tmp_obj->cPut( $elem_values_array['AD_ELEMENT_ID'], $last_id_elem, $elem_values_array['COLUMNNAME'], self::TABLENAME );
				$elem_values_array['AD_ELEMENT_ID'] = $last_id_elem;
				// se guarda
				$this->cPut( $elem_values_array, $save_changes );
				$last_id_elem++;
			}
			else
			{
				echo "<br/> El elemento ya esta en compiere base. <br/>";
			}
		}
		else
		{
			$elem_values_array = $this->cFindByExpression( $elem_name, false );
			$elemo_values_array = $this->cFindByExpression( $elem_name );
			if ( $elem_values_array['AD_ELEMENT_ID'] >= 5000000 )
			{
				echo " elemento extendido <br/>";
				$tmp_obj->cPut( $elemo_values_array['AD_ELEMENT_ID'], $elem_values_array['AD_ELEMENT_ID'], $elem_values_array['COLUMNNAME'], self::TABLENAME );
			}
		}
		
	} // end cMigrate

	public function cMigrateByParentId( $parent_id, $save_changes = true )
	{
		$id_old = $parent_id;
		$tmp_obj = new TAdMig( $save_changes );

		$this->load();

		$last_id_elem = $this->cLastID() + 1;
		$lista    = $this->cFindAllByParentId( $id_old );
		foreach ($lista as $elem_name)
		{
			$this->cMigrateByName( $elem_name, $save_changes ); 			
		}	
	} // end cMigrate


} // end class
?>