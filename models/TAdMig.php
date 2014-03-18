<?php 
//include '../utils.php';

//session_start();

class TAdMig
{
	const TABLENAME = 'T_AD_MIG';
	private $save_changes;

	function __construct( $save_changes = true ) 
	{
       $this->save_changes = $save_changes;
    }

	function cGetIDByOldID( $tablename, $old_id )
	{
		$username   = $_SESSION['user_origen'];
		$password   = $_SESSION['user_opw'];
		$connection = oci_connect( $username, $password, $_SESSION['ip_origen'] . '/XE' );

		$id_new = 0;
		$query = " SELECT ID_NEW FROM " . self::TABLENAME . " WHERE ID_OLD = $old_id AND TABLE_NAME LIKE '$tablename' ";

		$stmt = oci_parse( $connection, $query );
		if ( oci_execute( $stmt ) )
		{
			$nrows = oci_fetch_all($stmt, $res);
			if ( !empty($res['ID_NEW']) )
				$id_new = $res['ID_NEW'][0];
		}
		else{ 
	        $e = oci_error( $stmt ); 
	        echo $e['message']; 
	    }

	    oci_close( $connection );

		return $id_new;
	}

	public function cPut( $id_old, $id_new, $value, $tablename )
	{
		$username   = $_SESSION['user_origen'];
		$password   = $_SESSION['user_opw'];
		$connection = oci_connect( $username, $password, $_SESSION['ip_origen'] . '/XE' );

		$insert_tmp_q = dameElInsertParcialDeLaTabla( $connection, self::TABLENAME );
		$query = " $insert_tmp_q VALUES ( $id_old, $id_new, $value, '$tablename' ) ";
		echo "<br/> $query <br/>";
		
		$stmt = oci_parse( $connection, $query );
		if ( $this->save_changes )
		{
			if ( oci_execute( $stmt ) )
			{}
			else
			{
				$e = oci_error( $stmt ); 
	        	echo $e['message'];
			}
		}
		oci_close( $connection );
	}


	public function truncate()
	{
		$username   = $_SESSION['user_origen'];
		$password   = $_SESSION['user_opw'];
		$connection = oci_connect( $username, $password, $_SESSION['ip_origen'] . '/XE' );
		
		$query = ' Truncate table ' . self::TABLENAME ;
		echo "<br/> $query <br/>";

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

} // end class
?>