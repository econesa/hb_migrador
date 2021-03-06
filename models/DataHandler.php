﻿<?php 
//include '../utils.php';
include_once 'AdSequence.php';

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
	protected function load() 
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
		$error_msg = ''; 
		$last_id   = 0;

		$query = ' SELECT  MAX( ' . $this->tablename . '_ID ) 
				   FROM    COMPIERE.' . $this->tablename . ' t ';
		//echo " $query <br/>";

		if ( $this->connection != null )
			oci_close( $this->connection );

		try
		{
			$this->connection = oci_connect( $this->username_d, $this->password_d, $this->path_d );

			$stmt = oci_parse( $this->connection, $query );		
			if ( oci_execute( $stmt ) )
			{ 			
				while (($row = oci_fetch_row($stmt)) != false)
				{
					$last_id = $row[0];
				}
			}
			else
			{ 
				$error_msg = oci_error($stmt); 
				echo $e['message']; 
			}

			oci_close( $this->connection );
		}
		catch( Exception $exc )
		{
			echo "Exception: $exc->getMessage() <br>";
		}
		
		//echo " $this->tablename : $last_id <br/>";

		return $last_id;
	}

	/**/
	public function cCountByExpression( $value )
	{
		$query  = " SELECT COUNT(*) FROM $this->tablename t WHERE $this->expression LIKE '$value' ";
		//echo " <br> $query <br> ";
		$rs_count = -1;

		if ( $this->connection != null )
			oci_close( $this->connection );

		try
		{
			$this->connection = oci_connect( $this->username_d, $this->password_d, $this->path_d );
				
			$tarray = listarTiposDeTabla( $this->connection, $this->tablename );

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
		}
		catch( Exception $exc )
		{
			echo "Exception: $exc->getMessage() <br>";
		}
		
		return $rs_count;
	}

	/**/
	public function cFindAllByParentId( $parent_id )
	{		
		$e = 0;
		$data  = array();
		$query = " SELECT {$this->expression} 
				   FROM   COMPIERE.{$this->tablename} t 
				   JOIN   AD_COLUMN columna ON (columna.{$this->tablename}_ID = t.{$this->tablename}_ID) 
				   WHERE  columna.{$this->parent_tablename}_ID = $parent_id ";		
		//echo "<br> $query <br/>";		

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
			
			$stmt = oci_parse( $connection, $query );
			if ( oci_execute( $stmt ) ) 
			{				
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
			oci_close( $connection );	
		}
		catch( Exception $exc )
		{
			echo "Exception: $exc->getMessage() <br>";
		}
		
		return $data;
	} // end cFindAllByParentId

	/**/
	public function cFindByPK( $pk_id, $extern = true )
	{
		$i = 0;
		$values_array  = array();
		$query = " SELECT  t.*
				   FROM    COMPIERE.{$this->tablename} t
				   WHERE   {$this->tablename}_ID = {$pk_id} ";
		//echo "<br> $query <br>";
		
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

				// parsear data para poder colocarla en el insertar
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

			oci_close($connection);	
		}
		catch( Exception $exc )
		{
			echo "Exception: $exc->getMessage() <br>";
		}		
		
		return $values_array;
	}

	/* Busca según el Primary Key de la BD fuente, si no la encuentra devuelve -1. */
	public function cFindNameBySPK( $pk_id )
	{
		$value = '';
		$query  = " SELECT {$this->expression} FROM {$this->tablename} t
				    WHERE  {$this->tablename}_ID = $pk_id ";
		//echo "<br>*** $query ***<br>";

		try
		{
			$connection = oci_connect( $this->username_s, $this->password_s, $this->path_s );

			$tarray = listarTiposDeTabla( $connection, $this->tablename );
			
			$stmt  = oci_parse( $connection, $query );
			if ( oci_execute( $stmt ) )
			{
				if ( ($result = oci_fetch_row($stmt)) != false ) 
				{
					$value = $result[0];
				}
			}
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

		return $value;
	}


	public function cFindNameByDPK( $pk_id )
	{
		$value = '';
		$query  = " SELECT {$this->expression} FROM {$this->tablename} t
				    WHERE  {$this->tablename}_ID = $pk_id ";
		//echo "<br> $query <br>";

		$connection = oci_connect( $this->username_d, $this->password_d, $this->path_d );

		$tarray = listarTiposDeTabla( $connection, $this->tablename );
				
		$stmt  = oci_parse( $connection, $query );
		oci_execute( $stmt );
		
		if ( ($result = oci_fetch_row($stmt)) != false ) 
		{
			$value = $result[0];
		}
		oci_close($connection);
		
		return $value;
	}

	/**/
	public function cFindPkByExpression( $value )
	{
		$connection = oci_connect( $this->username_d, $this->password_d, $this->path_d );
		
		$rs_id  = -1;
		$tarray = listarTiposDeTabla( $connection, $this->tablename );

		$query  = " SELECT {$this->tablename}_ID FROM $this->tablename t WHERE $this->expression LIKE '$value' ";
		//echo "<br/> $query <br/>";

		$stmt = oci_parse( $connection, $query );
		if ( oci_execute( $stmt ) )
		{
			$row = oci_fetch_assoc( $stmt ); 
			$rs_id = $row[$this->tablename.'_ID'];		

		} // execute 
		else
		{
			$e = oci_error( $stmt ); 
	        echo $e['message']; 
		}
		
		oci_close($connection);
		
		return $rs_id;
	}

	/* Dado el id de la tabla del origen, se busca la del destino */
	public function cFindDPKBySPK( $spk_id )
	{
		$valor  = -1;
		$name   = $this->cFindNameBySPK( $spk_id );
		if ( !empty($table_name) )
        {
			$dpk_id = $this->cFindPkByExpression( $name );
			if ( $dpk_id != -1 )
				 $valor = $dpk_id;
			echo "<br/> $name: $spk_id ==> $dpk_id <br/>";
		}
		else
		{
			echo "<br/> No se encontro por nombre <br/>";
			$valor = 'NULL';
		}
		return $valor;
	}

	/* prepara el insert de una entidad y lo ejecuta */
	public function cPut( $values_array, $save_changes = true  )
	{		
		try 
		{
			$connection = oci_connect( $this->username_d, $this->password_d, $this->path_d );
			$insert_q   = dameElInsertParcialDeLaTabla( $connection, $this->tablename );

			unset( $values_array[ 'ISAPPROVED' ] ); 
			unset( $values_array[ 'DATEPROCESSED' ] ); 
			unset( $values_array[ 'SYNCHRONIZEDEFAULTS' ] ); 
			unset( $values_array[ 'SYSTEMSTATUS' ] );   

			$query = $insert_q . ' VALUES (' . implode(",", $values_array) . ')';
			echo "<br> $query <br/>";

			$stmt = oci_parse( $connection, $query );

			if ( $save_changes )
			{
				if ( oci_execute( $stmt ) )
				{
					echo "<br> insertado <br/>"; 
				}
				else
				{
					$e = oci_error( $stmt ); 
					echo $e['message'] . '<br/>'; 
				}	
			}			
			
			oci_close( $connection );		
		} 
		catch( Exception $exc )
		{
			echo "Exception: $exc->getMessage() <br>";
		}

		// actualizar secuencia
		$seq_obj   = new AdSequence(); 
		$seq_obj->cIncrease( $this->tablename, $save_changes );	
	
	}

	/* Calcula la diferencia que hay entre dos entidades y retorna el resultado en formato json */
	function diferenciar( $page, $rows, $offset )
	{
		$result = array();
		$offset2 = 0;

		$mip = explode(".", $this->path_d );
		$enlace = 'HBE_DESA_' . $mip[3]; //Database Link
		//$this->expression $this->expression 

		$query = 
			" SELECT   COUNT(*) 
			  FROM
	  		   (SELECT $this->expression		
			    FROM   COMPIERE.{$this->tablename} t
			      MINUS
			    SELECT $this->expression
			    FROM   COMPIERE.{$this->tablename}@$enlace t2)
			";
		//echo "<br/> $query <br/>";
		$connection = oci_connect( $this->username_s, $this->password_s, $this->path_s );
		
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
			        SELECT $this->expression
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