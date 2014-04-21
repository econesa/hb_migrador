<?php 
//include '../utils.php';

//session_start();

class AdSequence
{
	const TABLENAME         = 'AD_SEQUENCE';
	
	public function getTablename( )
	{
		return self::TABLENAME;
	}

	public function cLastID( )
	{
		$username   = $_SESSION['user_destino'];
		$password   = $_SESSION['user_dpw'];
		$connection = oci_connect( $username, $password, $_SESSION['ip_destino'] . '/XE' );

		$last_id = 0;
		$sql = 'SELECT  MAX(' . self::TABLENAME .'_ID)
				FROM    COMPIERE.' . self::TABLENAME . ' t';
		$stmt = oci_parse( $connection, $sql );
		//echo $sql;
		if ( oci_execute( $stmt ) )
		{		
			while (($row = oci_fetch_row($stmt)) != false)
			{
				$last_id = $row[0];
			}
		}
		else{ 
			$e = oci_error($stmt); 
			echo $e['message']; 
		}
		oci_close( $connection );
		
		return $last_id;
	}
	
	public function cFindByTablename( $tablename_value,  $save_changes )
	{
		$username   = $_SESSION['user_origen'];
		$password   = $_SESSION['user_opw'];
		$connection = oci_connect( $username, $password, $_SESSION['ip_origen'] . '/XE' );
		
		$values_array = array();
		$tarray = listarTiposDeTabla( $connection, self::TABLENAME );

		$query  =  ' SELECT * FROM ' . self::TABLENAME . ' t WHERE NAME LIKE ' . $tablename_value . ' '; 
		echo "<br/> $query <br/>";

		$stmt = oci_parse( $connection, $query );
		if ( $save_changes )
		{
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
				$e = oci_error($stmt); 
				echo $e['message'] . '<br/>'; 
			}
		}
		
		oci_close( $connection );

		return $values_array;
	}

	public function cIncrease( $save_changes )
	{
		$seq_no = $this->cLastID( );
		$query = " UPDATE AD_SEQUENCE
				   SET    CURRENTNEXT= $seq_no + INCREMENTNO
				   WHERE  NAME LIKE 'AD_Table' ";
		echo "<br> $query <br>";
		
		$username   = $_SESSION['user_destino'];
		$password   = $_SESSION['user_dpw'];
		$connection = oci_connect( $username, $password, $_SESSION['ip_destino'] . '/XE' );

		$stmt = oci_parse( $connection, $query );
		if ( $save_changes )
		{
			if ( oci_execute( $stmt ) )
			{
				echo '<br> actualizado <br>';
			}
			else
			{ 
				$e = oci_error( $stmt ); 
				echo $e['message'] . '<br/>'; 
			}
		}

		oci_close( $connection );
	}

	/**/
	public function cPut( $values_array, $save_changes )
	{
		$username   = $_SESSION['user_destino'];
		$password   = $_SESSION['user_dpw'];
		$connection = oci_connect( $username, $password, $_SESSION['ip_destino'] . '/XE' );
		
		$insert_q = dameElInsertParcialDeLaTabla( $connection, self::TABLENAME );	

		$query    = $insert_q . ' VALUES (' . implode(", ", $values_array) . ')';
		echo "<br> $query <br>";
		
		$stmt = oci_parse( $connection, $query );
		if ( $save_changes )
		{
			if ( oci_execute( $stmt ) )
			{
				echo '<br> insertado <br>';
			}
			else
			{ 
				$e = oci_error($stmt); 
				echo $e['message'] . '<br/>'; 
			}
		}

		oci_close( $connection );
	}

} // end class
?>