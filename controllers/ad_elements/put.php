<?php
include '../../utils.php';

session_start(); 
$ip_origen  = $_SESSION['ip_origen'];
$ip_destino = $_SESSION['ip_destino'];

$username = 'compiere';
$conn1_path = $ip_origen  . '/XE';
$conn2_path = $ip_destino . '/XE'; 
$mip = explode(".", $ip_destino);

$tablename  = 'AD_ELEMENT';
$expression = 'UPPER(COLUMNNAME)';

$data = json_decode( file_get_contents('php://input'), true );

//echo '<p>' . $tablename  . ' : ' . implode(", ", $tables_array) . '</p>';

$enlace_nombre = 'HBE_DESA_' . $mip[3]; //Database Link
$con_local 	= oci_connect( $username, 'compiere', $conn1_path );
$c2 		= oci_connect( $username, 'compiere', $conn2_path ); //'oracle'//

//echo $enlace_nombre . '<br/>';
//echo "Origen  : $username@$conn1_path <br>";

$insert_tmp_q = dameElInsertParcialDeLaTabla( $con_local, 'T_AD_MIG' );
$last_id_table = getLastIdTable( $con_local, $enlace_nombre, $tablename )+1;

//Corresponde a AD_Element
foreach ($data as $item)
{
	$insert_q = dameElInsertParcialDeLaTabla( $con_local, $tablename );
	$tarray = listarTiposDeTabla( $con_local, $tablename );
	
	// se buscan los datos completos de la fila
	$values_array = findByExpression( $con_local, $tablename, $expression, $item[$expression] );
	
	// guardar en tabla temporal	
	$columnname = $values_array[6];
	$tableid    = $values_array[1]; // guardar el id original
	$insertar_tmp_q_total = " $insert_tmp_q VALUES ( $tableid, $last_id_table, $columnname, '$tablename' ) ";
	//echo "<br/><br/> $insertar_tmp_q_total <br/><br/>";
	$stmt3 = oci_parse( $con_local, $insertar_tmp_q_total );
	oci_execute( $stmt3 );

	// se prepara consulta de migracion con id nuevo
	$values_array[0] = 0; 
	$values_array[1] = $last_id_table; // actualizo al ultimo id
	$iquery = $insert_q . ' VALUES (' . implode(",", $values_array) . ')';
	echo $iquery. '<br><br>';
	$stmt4 = oci_parse( $c2, $iquery );
	oci_execute( $stmt4 );
	//echo '<b>' . $columnname . '</b> procesada<br><br>';
	$last_id_table++;
}

oci_close($con_local);
oci_close($c2);



?>