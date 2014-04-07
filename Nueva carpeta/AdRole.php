<?php 
include_once '../../utils.php';
include_once 'DataHandler.php';

//session_start();

class AdRole extends DataHandler
{
	
	public function __construct()
	{
		parent::load();
		$this->parent_tablename = '';
		$this->tablename  = 'AD_ROLE';
		$this->expression = 'UPPER(T.NAME)'; // el indice asociativo esta dado en mayusculas
	}

	public function getTablename()
	{
		return $this->tablename;
	}

	/**/
	public static function cFindAllWhereNoManual( $value, $extern = true )
	{
		$values_array = array();
		$query  = " SELECT * FROM $this->tablename t WHERE t.IsManual='N' AND AD_CLIENT_ID=5000000 ";
		//echo "<br> $query <br/>";
		$connection   = null;

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
		
		}
		catch( Exception $exc )
		{
			echo "Exception: $exc->getMessage() <br>";
		}

		return $values_array;
	}
	
	/**/
	public function cMigrate( $parent_id, $save_changes = true )
	{
		
	} // end 


} // end class
?>