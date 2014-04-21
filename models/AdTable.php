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
	public function cFindByExpression( $value, $extern = true )
	{
		$query  = " SELECT * FROM $this->tablename t WHERE $this->expression LIKE '$value' ";
		//echo "<br/> $query <br/>";
		$values_array = array();

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
			} // execute 
			else
			{
				$e = oci_error( $stmt ); 
		        echo $e['message']; 
			}
			
			oci_close( $connection );
		}
		catch( Exception $exc )
		{
			echo "Exception: $exc->getMessage() <br>";
		}
		
		return $values_array;
	} // cFindByExpression

	/**/
	public function cFindAllByParentId( $parent_id )
	{		
		$data = array();

		$query = " SELECT {$this->expression} 
				   FROM   COMPIERE.{$this->tablename} t 
				   JOIN   {$this->parent_tablename} parent ON (parent.{$this->tablename}_ID = t.{$this->tablename}_ID) 
				   WHERE  parent.{$this->parent_tablename}_ID = $parent_id ";
		
		//echo "<br> $query <br/>";

		try
		{
			$connection = oci_connect( $this->username_s, $this->password_s, $this->path_s );

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
		
		}
		catch( Exception $exc )
		{
			echo "Exception: $exc->getMessage() <br>";
		}
		
		return $data;
	}

	/**/
	public function cFindByExpressionAndParentID( $parentTableName, $value, $parentID )
	{
		$values_array = array();
		$query = " SELECT * FROM $this->tablename t
				   JOIN  {$parentTableName} tmp ON ( tmp.{$this->tablename}_ID = t.{$this->tablename}_ID )
				   WHERE $this->expression LIKE '$value' AND {$parentTableName}_ID = $parentID ";
		//echo "<br> $query <br>";

		$connection = oci_connect( $this->username_s, $this->password_s, $this->path_s );

		$tarray = listarTiposDeTabla( $connection, $this->tablename );

		$stmt  = oci_parse( $connection, $query );
		
		if ( oci_execute( $stmt ) )
		{
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
		}
		else
		{
			$e = oci_error( $stmt ); 
	        echo $e['message']; 
		}
		
		oci_close($connection);

		return $values_array;
	}

	/**/
	public function setNulls( $values_array )
	{
		$v_array = $values_array;

		if ( empty($v_array['AD_VAL_RULE_ID']) 	) 	    $v_array['AD_VAL_RULE_ID']  = 'NULL';
		if ( empty($v_array['PO_WINDOW_ID']) 	) 	    $v_array['PO_WINDOW_ID']    = 'NULL';
		if ( empty($v_array['DATECOLUMN_ID']) 	) 	    $v_array['DATECOLUMN_ID']   = 'NULL';
		if ( empty($v_array['BASE_TABLE_ID']) 	) 	    $v_array['BASE_TABLE_ID']   = 'NULL';
		if ( empty($v_array['REFERENCED_TABLE_ID'])  )  $v_array['REFERENCED_TABLE_ID']   = 'NULL';

		return $v_array;
	}

	public function cMigrateByPK( $pk_id, $save_changes = true )
	{
		$entity_name = $this->cFindNameBySPK( $pk_id );
		$last_id_entity = $this->cMigrateByName( $entity_name, $this->cLastID() + 1, $save_changes );
		return $last_id_entity;	
	} // end cMigrateByPK

	/*  */
	public function cMigrateByName( $name, $last_id, $save_changes = true )
	{
		$entity_name = $name;
		$last_id_entity = $last_id;
		
		// verificar si no existe en el destino, en cuyo caso se migra.
		$exists = $this->cCountByExpression( $entity_name ); 
		if ($exists == 0) 
		{ 
			$values_array = $this->cFindByExpression( $entity_name );

			$values_array['AD_WINDOW_ID'] = 'NULL'; // Ignorar AD_Window_ID

			if ( $values_array[ $this->tablename . '_ID' ] >= 5000000 )
			{
				$values_array = $this->setNulls( $values_array );
				$values_array[ $this->tablename . '_ID' ] = $last_id_entity;
				echo "<br>{$this->tablename} :: migrando tabla $entity_name.... <br>";
				$this->cPut( $values_array, $save_changes );

				$seq_obj   = new AdSequence(); 
				$seq_array = $seq_obj->cFindByTablename( $values_array['TABLENAME'], $save_changes );
				$seq_array['AD_SEQUENCE_ID'] = $seq_obj->cLastID( ) + 1;
				$seq_array['CREATEDBY'] = $seq_array['UPDATEDBY'] = 100;		

				$seq_obj->cPut( $seq_array, $save_changes );

				// incrementar secuencia de AD_TABLE
				$seq_obj->cIncrease( $save_changes );	

			}
			else
			{
				$values_array = $this->cFindByExpression( $entity_name, false );
				$last_id_entity = $values_array[ $this->tablename . '_ID' ];
				echo "<br> La tabla ya esta en compiere original - $entity_name con ID:$last_id_entity <br>";		
			}
		}
		else
		{
			$values_array = $this->cFindByExpression( $entity_name, false );
			$last_id_entity = $values_array[ $this->tablename . '_ID' ];
			echo "<br> existe $entity_name con ID:$last_id_entity <br>";
		}	

		return $last_id_entity;

	} // end cMigrateByName

} // end class
?>