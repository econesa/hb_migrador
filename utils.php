 <?php

function cDiferenciarEstructuraEnJSON( $tablename, $page, $rows, $offset  )
{
	$result = array();
	
	$mip = explode(".", $_SESSION['ip_destino'] );
	$enlace_nombre = 'HBE_DESA_' . $mip[3]; //Database Link

	$conn1_path = $_SESSION['ip_origen'] . '/XE';
	$username1  = $_SESSION['user_origen'];
	$password1  = $_SESSION['user_opw'];
	$con_local  = oci_connect( $username1, $password1, $conn1_path );

	/*
	$conn2_path = $ip_destino . '/XE';
	$username2  = $_SESSION['user_destino'];
	$password2  = $_SESSION['user_dpw'];
	$c2 	    = oci_connect( $username, $password2, $conn2_path ); 
	*/
	
	$result = diferenciarEstructuraEnJSON( $con_local, $enlace_nombre, $tablename, $page, $rows, $offset );
	
	oci_close($con_local);
	
	return $result;
}

function diferenciarEstructuraEnJSON( $conn, $enlace, $tablename, $page, $rows, $offset  )
{
	$result = array();
	$offset2 = 0;
	// OR table_name LIKE \'HB%\', nullable "NULLABLE"

	$query = ' 	SELECT  table_name "TABLENAME", column_name "NAME", data_type "DATA_TYPE"
				FROM    user_tab_columns t
				WHERE   table_name LIKE \'M_%\' 
				 AND table_name NOT LIKE \'M_INOUT\' AND table_name NOT LIKE \'M_INOUTLINECONFIRM\' AND table_name NOT LIKE \'M_INOUT_CABECERA_V\' 
				 AND table_name NOT LIKE \'M_INOUT_CABECERA_VT\'  AND table_name NOT LIKE \'M_INOUT_LINEA_V\'  AND table_name NOT LIKE \'M_INOUT_LINEA_VT\' 
				 

				minus

				SELECT	table_name "TABLENAME", column_name "NAME", data_type "DATA_TYPE"
				FROM 	user_tab_columns@"HBE_DESA_143" t2
				WHERE 	table_name LIKE \'M_%\' 
				AND table_name NOT LIKE \'M_INOUT\' AND table_name NOT LIKE \'M_INOUTLINECONFIRM\' AND table_name NOT LIKE \'M_INOUT_CABECERA_V\' 
				 AND table_name NOT LIKE \'M_INOUT_CABECERA_VT\'  AND table_name NOT LIKE \'M_INOUT_LINEA_V\'  AND table_name NOT LIKE \'M_INOUT_LINEA_VT\'
				  
				';

	//echo "<br/>$query<br/>"; // OJO al imprimir no funciona UI

	$stmt = oci_parse( $conn, $query );
	
	if ( oci_execute( $stmt ) )
	{
		$row = oci_fetch_row($stmt);
		$result["total"] = $row[0];
		$offset2 = $offset + $rows;
	}
		
	$query = 
		" SELECT outer.*
		  FROM
		    (SELECT ROWNUM rn, inner.* FROM
  		      ( $query ) inner) outer
		  WHERE outer.rn>=$offset AND outer.rn<=$offset2		  
		";
	//echo '<br>' . $query . '<br><br>'; 
	$stmt = oci_parse( $conn, $query );

	if ( oci_execute( $stmt ) )
	{
		$obj_array = array();
		while ( ($row = oci_fetch_object($stmt)) != false )
		{
			array_push($obj_array, $row);
		}
		if (empty($obj_array)) array_push($obj_array, "$offset $rows");
		$result['rows'] =  $obj_array;
	}

	return json_encode($result);
}


function cDiferenciarEnJSON( $tablename, $expression, $page, $rows, $offset  )
{
	$result = array();
	
	$mip = explode(".", $_SESSION['ip_destino'] );
	$enlace_nombre = 'HBE_DESA_' . $mip[3]; //Database Link

	$conn1_path = $_SESSION['ip_origen'] . '/XE';
	$username1  = $_SESSION['user_origen'];
	$password1  = $_SESSION['user_opw'];
	$con_local  = oci_connect( $username1, $password1, $conn1_path );

	/*
	$conn2_path = $ip_destino . '/XE';
	$username2  = $_SESSION['user_destino'];
	$password2  = $_SESSION['user_dpw'];
	$c2 	    = oci_connect( $username, $password2, $conn2_path ); 
	*/
	
	$result = diferenciarEnJSON( $con_local, $enlace_nombre, $tablename, $expression, $page, $rows, $offset );
	
	oci_close($con_local);
	
	return $result;
}

function diferenciarEnJSON( $conn, $enlace, $tablename, $expression, $page, $rows, $offset  )
{
	$result = array();
	$offset2 = 0;

	//echo "<br/>$enlace<br/>";

	$query = " 	SELECT   COUNT(*) 
				FROM
		  		   (SELECT $expression 		
				    FROM   COMPIERE.$tablename t
				      MINUS
				    SELECT $expression 
				    FROM   COMPIERE.$tablename@$enlace t2)
				";
	//echo "<br/>$query<br/>"; // OJO al imprimir no funciona UI

	$stmt = oci_parse( $conn, $query );
	
	if ( oci_execute( $stmt ) )
	{
		$row = oci_fetch_row($stmt);
		$result["total"] = $row[0];
		$offset2 = $offset + $rows;
	}
		
	$query = 
		" SELECT outer.*
		  FROM
		    (SELECT ROWNUM rn, inner.* FROM
  		      ( SELECT $expression 		
		        FROM   COMPIERE.$tablename t
		       MINUS
		        SELECT $expression 
		        FROM   COMPIERE.$tablename@$enlace t2) inner) outer
		  WHERE outer.rn>=$offset AND outer.rn<=$offset2		  
		";
	//echo '<br>' . $query . '<br><br>'; 
	$stmt = oci_parse( $conn, $query );

	if ( oci_execute( $stmt ) )
	{
		$obj_array = array();
		while ( ($row = oci_fetch_object($stmt)) != false )
		{
			array_push($obj_array, $row);
		}
		if (empty($obj_array)) array_push($obj_array, "$offset $rows");
		$result['rows'] =  $obj_array;
	}

	return json_encode($result);
}

function diferenciar( $conn, $enlace, $tablename, $expression )
{
	$query = 
		" SELECT $expression 		
		  FROM   COMPIERE.$tablename t1
		 MINUS
		  SELECT $expression 
		  FROM   COMPIERE.$tablename@$enlace t2 ";
	// echo '<br>' . $query . '<br><br>';
	$stmt = oci_parse( $conn, $query );
	oci_execute( $stmt );
	$nrows = oci_fetch_all($stmt, $res);
	return $res[$expression];
}

function seleccionar( $conn, $tablename, $expression, $parentTableName, $parent_id )
{
	$query = 
		" SELECT $expression 		
		  FROM   COMPIERE.$tablename t1
		  WHERE  {$parentTableName}_ID = $parent_id
		";
	$stmt = oci_parse( $conn, $query );
	oci_execute( $stmt );
	$nrows = oci_fetch_all($stmt, $res);
	return $res[$expression];
}

function diferenciarAvd( $conn, $enlace, $tablename, $expression, $parentTableName, $parent_id )
{
	$query = 
		" SELECT $expression 		
		  FROM   COMPIERE.$tablename t1
		  WHERE  {$parentTableName}_ID = $parent_id
		 MINUS
		  SELECT $expression 
		  FROM   COMPIERE.$tablename@$enlace t2 
		  WHERE  {$parentTableName}_ID = $parent_id ";
	echo '<br>' . $query . '<br><br>';
	$stmt = oci_parse( $conn, $query );
	oci_execute( $stmt );
	$nrows = oci_fetch_all($stmt, $res);
	return $res[$expression];//
}

function getLastIdTable( $conn, $enlace, $tablename )
{
	$last_id = 0;
	$last_id_q =
		'SELECT 	MAX(' . $tablename .'_ID)
		 FROM   	COMPIERE.' .$tablename. '@' . $enlace . ' t1';
	$stmt = oci_parse( $conn, $last_id_q );
	oci_execute( $stmt );
	while (($row = oci_fetch_row($stmt)) != false) {
		$last_id = $row[0];
	}
	return $last_id;
}

function listarTiposDeTabla( $conn, $tablename )
{
	$query = 'SELECT * from ' . $tablename;
	$stmt = oci_parse( $conn, $query );
	oci_execute( $stmt );
	$ncols = oci_num_fields($stmt);
	$tarray = array();
	
	for ($i = 1; $i <= $ncols; $i++) {
		$tarray[ $i-1 ] = array();
		$tarray[ $i-1 ]['nombre'] = oci_field_name($stmt, $i);	
		$tarray[ $i-1 ]['tipo']   = oci_field_type($stmt, $i);
	}
	return $tarray;
}

function dameElInsertParcialDeLaTabla( $conn, $tablename )
{
	$query = 'SELECT * from ' . $tablename;
	$stmt = oci_parse( $conn, $query );
	oci_execute( $stmt );
	$ncols = oci_num_fields($stmt);
	$query = 'INSERT into ' . $tablename . ' (' . listarColumnasDeTabla( $conn, $tablename ) . ')';
	return $query;
}

function listarColumnasDeTabla( $conn, $tablename )
{
	$query = 'SELECT * from ' . $tablename;
	$stmt = oci_parse( $conn, $query );
	oci_execute( $stmt );
	$ncols = oci_num_fields($stmt);
	
	$query = '';
	$fieldname_array = array();
	for ($i = 1; $i <= $ncols; $i++) {
		if ($i!=1) $query .= ', ';
		$fieldname_array[$i]  = oci_field_name($stmt, $i);
		$query .= $fieldname_array[$i];
	}
	return $query;
}

function formatData( $datatype, $value )
{
	$return_value = '';
	switch( $datatype )
	{
		case 'VARCHAR2':
		case 'NVARCHAR2':					
		case 'CHAR':
		case 'DATETIME':
		case 'DATE': 
			$return_value = '\'' . preg_replace( array(0=>'/\'/'), array(0=>'\'\''), $value) . '\'';
			break;

		default:
			$return_value = $value;
	}
	return $return_value;
}

function formatEmpty( $datatype, $value )
{
	$return_value = '';
	switch( $datatype )
	{
		case 'VARCHAR2':
		case 'NVARCHAR2':					
		case 'CHAR':
		case 'DATETIME':
		case 'DATE': 
			$return_value = 'NULL';
			break;
		default:
			$return_value = 0;
	}
	return $return_value;
}

function findByExpression_col( $conn, $tablename, $expression, $value, $parentTableName, $parentID )
{
	$values_array = array();
	$tarray = listarTiposDeTabla( $conn, $tablename );
	//$column_value = $value; // deberia sanitizarse
	//print_r( $tarray ); 
	$query = " SELECT * FROM $tablename WHERE $expression LIKE '$value' AND {$parentTableName}_ID = $parentID";
	// echo $query. '<br><br>';
	
	$stmt  = oci_parse( $conn, $query );
	oci_execute( $stmt );
	
	if (($result = oci_fetch_row($stmt)) != false) 
	{
		$i = 0;	
		foreach ($result as $index=>$field)
		{
			if ( empty( $field ) )
			{
				$values_array[$i] = 'NULL';
			}
			else
			{
				$this->formatData( $tarray[$i]['tipo'], $field );				
			}	
			++$i;			
		}
	}
	return $values_array;
}

function cFindByExpression_col( $tablename, $expression, $value, $parentTableName, $parentID )
{
	$result = array();
	
	$mip = explode(".", $_SESSION['ip_destino'] );
	$enlace_nombre = 'HBE_DESA_' . $mip[3]; //Database Link

	$conn1_path = $_SESSION['ip_origen'] . '/XE';
	$username1  = $_SESSION['user_origen'];
	$password1  = $_SESSION['user_opw'];
	$connection = oci_connect( $username1, $password1, $conn1_path );
	
	$result = findByExpression_col( $connection, $tablename, $expression, $value, $parentTableName, $parentID );
	
	oci_close($connection);
	
	return $result;
}

function findByExpressionMultiple( $conn, $tablename, $expression_array, $values_array )
{
	$result_array = array();
	$tarray = listarTiposDeTabla( $conn, $tablename );
	//$column_value = $value; // deberia sanitizarse
	
	$query = " SELECT * FROM $tablename WHERE $expression_array[0] LIKE '$values_array[0]' ";
	$stmt  = oci_parse( $conn, $query );
	oci_execute( $stmt );
	
	if (($result = oci_fetch_row($stmt)) != false) 
	{
		$i = 0;	
		foreach ($result as $index=>$field)
		{
			if ( empty( $field ) )
			{
				$result_array[$i] = 'NULL';
			}
			else
			{
				switch( $tarray[$i]['tipo'] )
				{
					case 'VARCHAR2':
					case 'DATE':
					case 'CHAR':
					case 'DATETIME':
						$result_array[$i] = '\'' . $field . '\'';
						break;
					default:
						$result_array[$i] = $field;
				}				
			}	
			++$i;			
		}
	}
	return $result_array;
}

function cParsearParaInsertar( $values_array, $tablename )
{
	$result_array = array();

	$username   = $_SESSION['user_origen'];
	$password   = $_SESSION['user_opw'];
	$connection = oci_connect( $username, $password, $_SESSION['ip_origen'] . '/XE' );
	
	$tarray = listarTiposDeTabla( $connection, $tablename );
	
	echo '<br/>';
	print_r($tarray);
	echo '<br/><br/>';
	print_r($values_array);
	echo '<br/>';

	oci_close($connection);

	$i = 0;
	foreach ($values_array as $assoc_i => $field) //solucionar problema indice numerico, indice asociativo
	{
		switch( $tarray[$i]['tipo'] )
			{
				case 'VARCHAR2':
				case 'DATE':
				case 'CHAR':
				case 'DATETIME':
					$result_array[$assoc_i] = '\'' . $field . '\'';
					break;
				default:
					$result_array[$assoc_i] = $field;
			}				
		
		++$i;			
	}

	return $result_array;

}

function cFindByExpression( $tablename, $expression, $value )
{
	$result = array();
	
	$mip = explode(".", $_SESSION['ip_destino'] );
	$enlace_nombre = 'HBE_DESA_' . $mip[3]; //Database Link

	$conn1_path = $_SESSION['ip_origen'] . '/XE';
	$username1  = $_SESSION['user_origen'];
	$password1  = $_SESSION['user_opw'];
	$con_local  = oci_connect( $username1, $password1, $conn1_path );
	
	$result = findByExpression( $con_local, $tablename, $expression, $value );
	
	oci_close($con_local);
	
	return $result;
}

function findByExpression( $conn, $tablename, $expression, $value )
{
	$values_array = array();
	$tarray = listarTiposDeTabla( $conn, $tablename );
	// $column_value = $value; // deberia sanitizarse
	// print_r( $tarray ); 
	$query = " SELECT * FROM $tablename WHERE $expression LIKE '$value' ";
	// echo '--' . $query. '<br><br>';
	
	$stmt  = oci_parse( $conn, $query );
	if ( oci_execute( $stmt ) )
	{
		if (($result = oci_fetch_row($stmt)) != false) 
		{
			$i = 0;	
			foreach ($result as $index=>$field)
			{
				// echo "{$tarray[$i]['tipo']} : $field <br/><br/>";
				if ( empty( $field ) )
				{
					$values_array[$i] = 'NULL';
				}
				else
				{
					switch( $tarray[$i]['tipo'] )
					{
						case 'VARCHAR2':
						case 'CHAR':
							$values_array[$i] = '\'' . $field . '\'';
							break;
						case 'DATETIME':
							$date = date_create( $field );
							$values_array[$i] = '\'' . date_format($date, 'd/m/y H:i:s') . '\'';
							break;
						case 'DATE':
							$date = date_create( $field );
							//echo $field . " =? " . date_format($date, 'd/m/Y') . '<br/>'; // 
							$values_array[$i] = 'TO_DATE(\'' . date_format($date, 'd/m/y') . '\', \'dd/mm/yy\')';
							break;
						default:
							$values_array[$i] = $field;
					}				
				}	
				++$i;			
			}
		}
	} // execute 
	else
	{
		$e = oci_error( $stmt ); 
        echo $e['message']; 
	}

	return $values_array;
}

function trunTable()
{

	$username   = $_SESSION['user_origen'];
	$password   = $_SESSION['user_opw'];
	$connection = oci_connect( $username, $password, $_SESSION['ip_origen'] . '/XE' );
	
	$query = " truncate table T_Ad_Mig ";

	$stmt  = oci_parse( $connection, $query );
	if ( oci_execute( $stmt ) )
	{

	}
	else
	{
		$e = oci_error( $stmt ); 
        echo $e['message']; 
	}
	oci_close($connection);

}

?>