<?php 
//include '../utils.php';
include_once 'DataHandler.php';

//session_start();

abstract class DataHandler
{
	protected $parent_tablename  = '';
	protected $tablename  = '';
	protected $expression = '';

	protected $username_s;
	protected $password_s;
	protected $path_s;

	protected $username_d;
	protected $password_d;
	protected $path_d;

	protected $connection;

	/**/
	public function load() 
	{
	   $this->username_d  = $_SESSION['user_destino'];
	   $this->password_d  = $_SESSION['user_dpw'];
	   $this->path_d 	  = $_SESSION['ip_destino'] . '/XE';

	   $this->username_s  = $_SESSION['user_origen'];
	   $this->password_s  = $_SESSION['user_opw'];
	   $this->path_s 	  = $_SESSION['ip_origen'] . '/XE';
    }

    /**/
	public function getTablename( )
	{
		return $this->tablename;
	}

	/**/
	public function getExpression( )
	{
		return $this->expression;
	}

	/**/
	public function cLastID( )
	{
		if ( $this->connection != null )
			oci_close( $this->connection );

		$this->connection = oci_connect( $this->username_d, $this->password_d, $this->path_d );

		$last_id = 0;
		$query = ' SELECT  MAX( ' . $this->tablename . '_ID ) 
				   FROM    COMPIERE.' . $this->tablename . ' t ';
		//echo " $query <br/>";

		$stmt = oci_parse( $this->connection, $query );		
		if ( oci_execute( $stmt ) )
		{ 			while (($row = oci_fetch_row($stmt)) != false)
			{
				$last_id = $row[0];
			}
		}
		else
		{ 
			$e = oci_error($stmt); 
			echo $e['message']; 
		}

		oci_close( $this->connection );
		
		echo " $this->tablename : $last_id <br/>";

		return $last_id;
	}

	/**/
	public function cCountByExpression( $value )
	{
		if ( $this->connection != null )
			oci_close( $this->connection );

		$this->connection = oci_connect( $this->username_d, $this->password_d, $this->path_d );
		
		$rs_count = -1;
		$tarray = listarTiposDeTabla( $this->connection, $this->tablename );

		$query  = " SELECT COUNT(*) FROM $this->tablename t WHERE $this->expression LIKE '$value' ";
		echo " <br> $query <br> ";

		$stmt = oci_parse( $this->connection, $query );
		if ( oci_execute( $stmt ) )
		{
			$rs_count = oci_fetch_assoc( $stmt );
			$rs_count = $rs_count['COUNT(*)'];
		}
		else
		{
			$e = oci_error( $stmt ); 
	        echo $e['message']; 
		}
		
		oci_close( $this->connection );
		
		return $rs_count;
	}

	/**/
	public function cFindAllByParentId( $parent_id )
	{
		$connection = oci_connect( $this->username_s, $this->password_s, $this->path_s );
		
		$query = " SELECT {$this->expression} 
				   FROM   COMPIERE.{$this->tablename} t 
				   JOIN   AD_COLUMN columna ON (columna.{$this->tablename}_ID = t.{$this->tablename}_ID) 
				   WHERE  columna.{$this->parent_tablename}_ID = $parent_id ";
		
		echo "<br> $query <br/>";
		
		$stmt = oci_parse( $connection, $query );
		if ( oci_execute( $stmt ) ) 
		{
			$e = 0;
			$data = array();
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

	/**/
	public function cFindByPK( $pk_id )
	{
		$values_array  = array();
		
		$connection = oci_connect( $this->username_s, $this->password_s, $this->path_s );
		
		$tarray = listarTiposDeTabla( $connection, $this->tablename );

		$query = " SELECT  t.*
				   FROM    COMPIERE.{$this->tablename} t
				   WHERE   {$this->tablename}_ID = {$pk_id} ";
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
	}

	/* Calcula la diferencia que hay entre dos entidades y retorna el resultado en formato json */
	function diferenciar( $page, $rows, $offset )
	{
		$result = array();
		$offset2 = 0;

		$connection = oci_connect( $this->username_s, $this->password_s, $this->path_s );

		$mip = explode(".", $this->path_d );
		$enlace = 'HBE_DESA_' . $mip[3]; //Database Link

		$query = 
			" SELECT   COUNT(*) 
			  FROM
	  		   (SELECT $this->expression 		
			    FROM   COMPIERE.{$this->tablename} t
			      MINUS
			    SELECT $expression 
			    FROM   COMPIERE.{$this->tablename}@$enlace t2)
			";
		$stmt = oci_parse( $conn, $query );
		
		if ( oci_execute( $stmt ) )
		{
			$row = oci_fetch_row( $stmt );
			$result["total"] = $row[0];
			$offset2 = $offset + $rows;
		}
			
		$query = 
			" SELECT outer.*
			  FROM
			    (SELECT ROWNUM rn, inner.* FROM
	  		      ( SELECT $this->expression 		
			        FROM   COMPIERE.{$this->tablename} t
			       MINUS
			        SELECT $expression 
			        FROM   COMPIERE.{$this->tablename}@$enlace t2) inner) outer
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


} // end class
?>