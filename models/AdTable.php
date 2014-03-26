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
	
	public function __construct()
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
		//echo "<br/> $query <br/>";

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

	/*  */
	public function cMigrateByName( $name, $last_id, $save_changes = true )
	{
		$entity_name = $name;
		$last_id_table = $last_id;

		/*
		$seq_obj   = new AdSequence(); // TODO: verificar si la secuencia ya existe
		$seq_array = $seq_obj->cFindByTablename( $values_array['TABLENAME'], $save_changes );
		$seq_array['AD_SEQUENCE_ID'] = $seq_obj->cLastID( ) + 1;
		$seq_obj->cPut( $seq_array, $save_changes );
		*/

		// verificar si el reference esta en el origen, en cuyo caso se migra.
		$exists = $this->cCountByExpression( $entity_name ); 
		if ($exists == 0) 
		{ 
			$values_array = $this->cFindByExpression( $entity_name );

			$values_array['AD_WINDOW_ID']  = 'NULL'; // Ignorar AD_Window_ID

			if ( $values_array[ $this->tablename . '_ID' ] >= 5000000 )
			{
				echo " elemento extendido <br/>";
				echo '<br/> se coloca val_rule en NULL <br/>';
				$values_array['AD_VAL_RULE_ID'] = 'NULL';
				$values_array['PO_WINDOW_ID'] = 'NULL';
				$values_array['DATECOLUMN_ID'] = 'NULL';
				$values_array['BASE_TABLE_ID'] = 'NULL';
				$values_array['REFERENCED_TABLE_ID'] = 'NULL';

				$values_array[ $this->tablename . '_ID' ] = $last_id_table;
				$this->cPut( $values_array, $save_changes );
				$last_id_table++;
			}
		}

	} // end cMigrateByName

} // end class
?>