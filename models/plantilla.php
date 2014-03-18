<?php 
//include '../utils.php';

//session_start();

class p
{
	private $tablename  = 'T_AD_MIG';

	public function cPut( $id_old, $id_new, $value, $tablename  )
	{
		$conn1_path = $_SESSION['ip_origen'] . '/XE';
		$username1  = $_SESSION['user_origen'];
		$password1  = $_SESSION['user_opw'];
		$connection = oci_connect( $username1, $password1, $conn1_path );

		
		
		oci_close($connection);
		oci_close($c2);	
	}

}
?>