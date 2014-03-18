<?php 
include '../../utils.php';

session_start();
$ip_origen  = $_SESSION['ip_origen'];	//'192.168.60.149/XE'; //
$ip_destino = $_SESSION['ip_destino'];

$username   = 'compiere';
$password1  = 'compiere'; //'oracle'//
$password2  = 'compiere'; //'oracle'//

$conn1_path = $ip_origen  . '/XE';
$conn2_path = $ip_destino . '/XE';
$mip = explode(".", $ip_destino);

$enlace_nombre = 'HBE_DESA_' . $mip[3]; //Database Link
$con_local = oci_connect( $username, $password1, $conn1_path );
$c2 	   = oci_connect( $username, $password2, $conn2_path ); 

$insert_tmp_q = dameElInsertParcialDeLaTabla( $con_local, 'T_AD_MIG' );

$page = isset($_POST['page']) ? intval($_POST['page']) : 1;
$rows = isset($_POST['rows']) ? intval($_POST['rows']) : 10;
$offset = ($page-1)*$rows;

$tablename  = 'AD_WINDOW';
$expression = 'UPPER(NAME)';
//$last_id_table = getLastIdTable($con_local, $enlace_nombre, $tablename)+1;
$json_rs = diferenciarEnJSON( $con_local, $enlace_nombre, $tablename, $expression, $page, $rows, $offset );

echo $json_rs;
?>