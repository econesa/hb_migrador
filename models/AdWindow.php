<?php 
//include '../utils.php';
include_once 'DataHandler.php';

//session_start();

class AdWindow extends DataHandler
{
	const  TABLENAME   = 'AD_WINDOW';
	
	public function getTablename( )
	{
		return self::TABLENAME;
	}

	public function load()
	{
		parent::load();
		$this->parent_tablename  = '';
		$this->tablename  = 'AD_WINDOW';
		$this->expression = 'UPPER(T.NAME)';
	}

	public function getExpression( )
	{
		return $this->expression;
	}
	
	function cFindByExpression( $value )
	{
		$values_array = array();
		
		$username   = $_SESSION['user_origen'];
		$password   = $_SESSION['user_opw'];
		$connection = oci_connect( $username, $password, $_SESSION['ip_origen'] . '/XE' );
		
		$tarray = listarTiposDeTabla( $connection, $this->tablename );
		$query = " SELECT * FROM $this->tablename t WHERE $this->expression LIKE '$value' ";
		echo "<br><br> $query <br>";

		$stmt  = oci_parse( $connection, $query );
				
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

		oci_close($connection);

		return $values_array;
	} // end cFindByExpression

	public function cPut( $values_array )
	{
		$username   = $_SESSION['user_destino'];
		$password   = $_SESSION['user_dpw'];
		$connection = oci_connect( $username, $password, $_SESSION['ip_destino'] . '/XE' );
		
		$insert_q = dameElInsertParcialDeLaTabla( $connection, $this->tablename );
		$query    = $insert_q . ' VALUES (' . implode(",", $values_array) . ')';
		echo "<br> $query <br>";

		$stmt = oci_parse( $connection, $query );
		if (!oci_execute( $stmt ))
		{
			$e = oci_error($stmt); 
			echo $e['message']; 
		}
		oci_close( $connection );
	} // end cPut

	/* Migra una ventana dado su nombre y el id que debe tener.
	**/
	public function cMigrate( $values_array, $table_id, $save_changes = true )
	{
		$tmp_obj = new TAdMig( $save_changes );
		$tmp_obj->truncate();
		
		$this->load();

		$id_old    = $values_array['AD_WINDOW_ID'];

		echo "<br>** migrando ventana {$values_array['NAME']}.... **<br>";
/*
		$seq_obj   = new AdSequence(); // TODO: verificar si la secuencia ya existe
		$seq_array = $seq_obj->cFindByTablename( $values_array['NAME'], $save_changes );
		$seq_array['AD_SEQUENCE_ID'] = $seq_obj->cLastID( ) + 1;
		$seq_obj->cPut( $seq_array, $save_changes );
*/
		$tmp_obj->cPut( $id_old, $table_id, $values_array['NAME'], $this->tablename );

		// se prepara consulta de migracion con id nuevo
		$values_array['AD_CLIENT_ID'] = $values_array['AD_ORG_ID'] = 0;
		$values_array['AD_IMAGE_ID']  = $values_array['AD_CTXAREA_ID'] = $values_array['AD_COLOR_ID']  = 'NULL';
		$values_array['AD_WINDOW_ID'] = $table_id; // actualizo al ultimo id
		//echo '<br/>'; print_r( $values_array ); echo '<br/>';

		$this->cPut( $values_array, $save_changes );	
		//$this->cPut( $this->prepareValues($values_array));		
	}

} // end class

?>