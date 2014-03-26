<?php 
include_once 'AdElement.php';
include_once 'AdReference.php';
include_once 'AdSequence.php';
include_once 'AdValRule.php';
include_once 'DataHandler.php';

//session_start();

class AdTable extends DataHandler
{
	const TABLENAME  = 'AD_TABLE';
	
	/**/
	public function getTablename( )
	{
		return self::TABLENAME;
	}
	
	public function load()
	{
		parent::load();
		$this->parent_tablename = 'AD_TAB';
		$this->tablename  = 'AD_TABLE';
		$this->expression = 'UPPER(T.TABLENAME)';
	}

	/**/
	public function cFindByExpression( $value )
	{
		$connection = oci_connect( $this->username_s, $this->password_s, $this->path_s );
		
		$values_array = array();
		$tarray = listarTiposDeTabla( $connection, $this->tablename );

		$query  = " SELECT * FROM $this->tablename t WHERE $this->expression LIKE '$value' ";
		echo "<br/> $query <br/>";

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
		else
		{
			$e = oci_error( $stmt ); 
	        echo $e['message']; 
		}
		
		oci_close($connection);
		
		return $values_array;
	}

	/**/
	public function cFindAllByParentId( $parent_id )
	{
		$connection = oci_connect( $this->username_s, $this->password_s, $this->path_s );
		$data = array();

		$query = " SELECT {$this->expression} 
				   FROM   COMPIERE.{$this->tablename} t 
				   JOIN   {$this->parent_tablename} parent ON (parent.{$this->tablename}_ID = t.{$this->tablename}_ID) 
				   WHERE  parent.{$this->parent_tablename}_ID = $parent_id ";
		
		//echo "<br> $query <br/>";
		
		$stmt = oci_parse( $connection, $query );
		if ( oci_execute( $stmt ) ) 
		{
			$e = 0;			
			while ( ($row = oci_fetch_assoc($stmt)) != false ) 
			{
				$data[$e] = $row[$this->expression];
			    $e++;
			}
		}
		else
		{
			$e = oci_error( $stmt ); 
	        echo $e['message']; 
		}
		// oci_free_statement($stmt);
		oci_close($connection);
		
		return $data;
	}

	/*
	public function cFindByPK( $pk_id )
	{
		$values_array  = array();
		$tablename  = self::TABLENAME;
		
		$username   = $_SESSION['user_origen'];
		$password   = $_SESSION['user_opw'];
		$connection = oci_connect( $username, $password, $_SESSION['ip_origen'] . '/XE' );
		
		$tarray = listarTiposDeTabla( $connection, self::TABLENAME );

		$query = 
			" SELECT  t.*
			  FROM    COMPIERE.{$tablename} t
			  WHERE   {$tablename}_ID = {$pk_id} ";
		echo "<br> $query <br>";

		$stmt = oci_parse( $connection, $query );

		if ( oci_execute( $stmt ) )
		{
			$values_array = oci_fetch_assoc( $stmt ); 
			$i = 0;

			// parsear data para poder colocarla en el insertar
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
		else
		{
			$e = oci_error( $stmt ); 
	        echo $e['message']; 
		}

		oci_close($connection);
		
		return $values_array;
	}*/

	/**/
	function cFindByExpressionAndParentID( $parentTableName, $value, $parentID )
	{
		$values_array = array();
		
		$connection = oci_connect( $this->username_s, $this->password_s, $this->path_s );

		$tarray = listarTiposDeTabla( $connection, $this->tablename );
		
		$query = " SELECT * FROM $this->tablename t
				   JOIN  {$parentTableName} tmp ON ( tmp.{$this->tablename}_ID = t.{$this->tablename}_ID )
				   WHERE $this->expression LIKE '$value' AND {$parentTableName}_ID = $parentID ";
		echo "<br> $query <br>";
		
		$stmt  = oci_parse( $connection, $query );
		oci_execute( $stmt );
		
		if (($result = oci_fetch_row($stmt)) != false) 
		{
			$i = 0;	
			foreach ($result as $index=>$field)
			{
				if ( empty( $field ) )
				{
					$values_array[$i] = formatEmpty( $tarray[$i]['tipo'], $field );
				}
				else
				{
					$values_array[$i] = formatData( $tarray[$i]['tipo'], $field );			
				}
				++$i;
			}
		}
		oci_close($connection);

		return $values_array;
	}

	/* Debido a que algunos campos quedan en vacios, se inicializan en 0 o NULL */
	public function prepareValues( $values_array )
	{
		$tmp_obj   = new TAdMig();

		$values_array['AD_CLIENT_ID'] = 0; // AD_Client_ID
		$values_array['AD_ORG_ID']    = 0; // AD_Org_ID
		$values_array['AD_TABLE_ID']  = $tmp_obj->cGetIDByOldID( $this->tablename, $values_array['AD_TABLE_ID'] );
		$values_array['AD_WINDOW_ID']  = 'NULL'; // Ignorar AD_Window_ID
		$values_array['DATECOLUMN_ID'] = 'NULL';
		$values_array['BASE_TABLE_ID'] = 'NULL';
		$values_array['REFERENCED_TABLE_ID'] = 'NULL';

		if ($values_array['AD_VAL_RULE_ID'] == 0) 
			$values_array['AD_VAL_RULE_ID'] = 'NULL';

		if ($values_array['PO_WINDOW_ID'] == 0) 
			$values_array['PO_WINDOW_ID'] = 'NULL';
		
		return $values_array;
	}

	/* prepara y ejecuta insertar de una tabla */
	public function cPut( $values_array, $save_changes )
	{
		$connection = oci_connect( $this->username_d, $this->password_d, $this->path_d );

		$insert_q = dameElInsertParcialDeLaTabla( $connection, $this->tablename ); 
		$query    = $insert_q . ' VALUES (' . implode(", ", $values_array) . ')'; 
		echo "<br/> $query <br/>";

		$stmt = oci_parse( $connection, $query );
		if ( $save_changes && !oci_execute( $stmt ) )
		{ 
			$e = oci_error($stmt); 
			echo $e['message'] . '<br/>'; 
		} 
		oci_close( $connection );
	}

	/* Migra una tabla dado su nombre y el id que debe tener.
	**/
	public function cMigrate( $values_array, $table_id, $save_changes = true )
	{
		$tmp_obj = new TAdMig( $save_changes );
		$tmp_obj->truncate();
		
		$this->load();

		$id_old  = $values_array['AD_TABLE_ID'];

		echo '<br>** migrando elementos.... **<br>';
		$elem_obj  = new AdElement();
		$elem_obj->cMigrateByParentId( $values_array['AD_TABLE_ID'], $save_changes );

		echo '<br>** migrando valores referenciales.... **<br>';
		$ref_obj  = new AdReference(); 
		$ref_obj->cMigrateByParentId( $values_array['AD_TABLE_ID'], $save_changes );
		
		echo '<br>** migrando value rules .... **<br>';
		$valrule_obj  = new AdValRule(); 
		$valrule_obj->cMigrateByParentId( $values_array['AD_TABLE_ID'], $save_changes );
		
		echo '<br>** migrando tablas.... **<br>';

		$seq_obj   = new AdSequence(); // TODO: verificar si la secuencia ya existe
		$seq_array = $seq_obj->cFindByTablename( $values_array['TABLENAME'], $save_changes );
		$seq_array['AD_SEQUENCE_ID'] = $seq_obj->cLastID( ) + 1;
		$seq_obj->cPut( $seq_array, $save_changes );

		// guardar ids en tabla temporal		
		$tmp_obj->cPut( $id_old, $table_id, $values_array['TABLENAME'], $this->tablename );
		// se prepara consulta de migracion con id nuevo
		$this->cPut( $this->prepareValues($values_array), $save_changes );	// TODO: verificar si la tabla ya existe (caso migrar por ventana)


		$datatablename = $this->cFindNameBySPK( $id_old  );
		if ( !empty($datatablename) )
		{
			echo "<br> Tabla $datatablename <br>";
			echo "<br> fuente: $id_old <br>";		
			$id_new = $this->cFindPkByExpression( $datatablename );
			echo "<br> destino: $id_new <br>";
		}	
	}

} // end class
?>