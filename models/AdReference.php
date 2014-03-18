<?php 
include_once 'DataHandler.php';

//session_start();

class AdReference extends DataHandler
{
	const TABLENAME      = 'AD_REFERENCE';

	/**/
	public function getTablename( )
	{
		return self::TABLENAME;
	}

	public function load()
	{
		parent::load();
		$this->parent_tablename  = 'AD_TABLE';
		$this->tablename  = 'AD_REFERENCE';
		$this->expression = 'UPPER(T.NAME)';
	}

	/**/
	public function getExpression( )
	{
		return $this->expression;
	}

	/**/
	public function cGet_Value( $parent_id )
	{
		$data = array();
		$tablename  = self::TABLENAME;
		$username1  = $_SESSION['user_origen'];
		$password1  = $_SESSION['user_opw'];
		$connection = oci_connect( $username1, $password1, $_SESSION['ip_origen'] . '/XE' );

		$query = " SELECT UPPER(t.NAME)
				   FROM   {$tablename} t
				   JOIN   AD_COLUMN columna ON (columna.AD_REFERENCE_VALUE_ID = t.AD_REFERENCE_ID)
				   WHERE  columna.AD_TABLE_ID = $parent_id ";
		
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
		$tablename  = self::TABLENAME;
		$connection = null;

		if ( $extern )
		{
			$username   = $_SESSION['user_origen'];
			$password   = $_SESSION['user_opw'];
			$connection = oci_connect( $username, $password, $_SESSION['ip_origen'] . '/XE' );
		}
		else
		{
			$username   = $_SESSION['user_destino'];
			$password   = $_SESSION['user_dpw'];
			$connection = oci_connect( $username, $password, $_SESSION['ip_destino'] . '/XE' );
		}
				
		$values_array = array();
		$tarray = listarTiposDeTabla( $connection, self::TABLENAME );

		$query  = " SELECT * FROM $tablename t WHERE $this->expression LIKE '$value' ";
		echo " <br> $query <br> ";

		$stmt = oci_parse( $connection, $query );
		if ( oci_execute( $stmt ) )
		{
			$values_array = oci_fetch_assoc( $stmt ); 
			if (!empty($values_array))
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
						$values_array[$indice] = formatData( $tarray[$i]['tipo'], $field );
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
	
	/**/
	public function cPut( $values_array, $save_changes = true )
	{
		$username1  = $_SESSION['user_origen'];
		$password1  = $_SESSION['user_opw'];
		$connection = oci_connect( $username1, $password1, $_SESSION['ip_origen'] . '/XE' );
		
		$insert_q = dameElInsertParcialDeLaTabla( $connection, self::TABLENAME );	
		
		oci_close($connection);	
		
		$username2  = $_SESSION['user_destino'];
		$password2  = $_SESSION['user_dpw'];
		$c2 		= oci_connect( $username2, $password2, $_SESSION['ip_destino'] . '/XE' );

		$query = $insert_q . ' VALUES (' . implode(",", $values_array) . ')';
		echo "<br> $query <br/>";
		
		$stmt = oci_parse( $c2, $query );
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

	public function cMigrateByParentId( $parent_id, $save_changes = true )
	{
		$id_old = $parent_id;
		$tmp_obj = new TAdMig( $save_changes );

		$this->load();

		$last_id_refv = $this->cLastID() + 1; 
		$refv_list    = $this->cGet_Value( $id_old ); // TODO: Actualizar el nombre de la función.
		foreach ($refv_list as $referencev_name) 
		{ 
			// verificar si el reference esta en el origen, en cuyo caso se migra.
			$refv_exists = $this->cCountByExpression( $referencev_name ); 
			echo " $refv_exists <br/>";
			if ($refv_exists == 0) 
			{ 
				$refv_values_array = $this->cFindByExpression( $referencev_name );
				if ( $refv_values_array['AD_REFERENCE_ID'] >= 5000000 )
				{
					echo " elemento extendido <br/>";
					$tmp_obj->cPut( $refv_values_array['AD_REFERENCE_ID'], $last_id_refv, $refv_values_array['NAME'], self::TABLENAME );
					$refv_values_array['AD_REFERENCE_ID'] = $last_id_refv;
					$this->cPut( $refv_values_array, $save_changes );
					$last_id_refv++;
				}
			}
			else
			{
				$refv_values_array = $this->cFindByExpression( $referencev_name, false );
				$refvo_values_array = $this->cFindByExpression( $referencev_name );
				if ( $refv_values_array['AD_REFERENCE_ID'] >= 5000000 )
				{
					echo " elemento extendido <br/>";
					$tmp_obj->cPut( $refvo_values_array['AD_REFERENCE_ID'], $refv_values_array['AD_REFERENCE_ID'], $refv_values_array['NAME'], self::TABLENAME );
				}
			}
		} 

	} // end cMigrate

} // end class

?>