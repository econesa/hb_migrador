<html> 
 <head> 
 </head> 
 <body> 
 
<?php
include 'utils.php';
//-------------------------------------------//

$tablename  = 'AD_TABLE';
$expression = 'UPPER(TABLENAME)';

$tables_array = array();

//echo '<p>' . $tablename  . ' : ' . implode(", ", $tables_array) . '</p>';

$tablename_array = $_POST;
if ( empty($_POST[$tablename]) )
{
	echo "empty array";
	exit;
}
if ( empty($_POST['ip_origen']) || empty($_POST['ip_destino']) )
{
	echo "wrong ips";
	exit;
}

$ip_origen  = $_POST['ip_origen'];
$ip_destino = $_POST['ip_destino'];

$username = 'compiere';
$conn1_path = $ip_origen  . '/XE';
$conn2_path = $ip_destino . '/XE'; 
$mip = explode(".", $ip_destino);


$enlace_nombre = 'HBE_DESA_' . $mip[3]; //Database Link
$con_local 	= oci_connect( $username, 'compiere', $conn1_path );
$c2 		= oci_connect( $username, 'compiere', $conn2_path ); //'oracle'//

echo $enlace_nombre . '<br/>';
echo "Origen  : $username@$conn1_path <br>";

$insert_tmp_q = dameElInsertParcialDeLaTabla( $con_local, 'T_AD_MIG' );
$last_id_table = getLastIdTable( $con_local, $enlace_nombre, $tablename )+1;

//print_r($tablename_array[$tablename]);

$tablename2    = 'AD_COLUMN';
$expression2   = 'UPPER(COLUMNNAME)'; //, 'AD_TABLE_ID');
$last_id_table2 = getLastIdTable($con_local, $enlace_nombre, $tablename2)+1;

foreach ($tablename_array[$tablename] as $item)
{
	$insert_q = dameElInsertParcialDeLaTabla( $con_local, $tablename );
	$tarray = listarTiposDeTabla( $con_local, $tablename );
	
	// se buscan los datos completos de la fila
	$values_array = findByExpression( $con_local, $tablename, $expression, $item );
	// print_r($values_array);
	
	// guardar en tabla temporal
	$values_array[0] = 0; // AD_Client_ID
	$values_array[1] = 0; // AD_Org_ID
	$values_array[4] = 'NULL'; // Ignorar AD_Window_ID
	$tableid  = $values_array[2]; // guardar el id original
	$columnname = $values_array[30];
	$insertar_tmp_q_total = " $insert_tmp_q VALUES ( $tableid, $last_id_table, $columnname, '$tablename' ) ";
	//echo "<br/><br/> $insertar_tmp_q_total <br/><br/>";
	$stmt3 = oci_parse( $con_local, $insertar_tmp_q_total );
	oci_execute( $stmt3 );

	// se prepara consulta de migracion con id nuevo
	$values_array[2] = $last_id_table; // actualizo al ultimo id
	$iquery = $insert_q . ' VALUES (' . implode(",", $values_array) . ')';
	//echo $iquery. '<br><br>';
	$stmt4 = oci_parse( $c2, $iquery );
	oci_execute( $stmt4 );
	echo '----------------------<b>' . $columnname . '</b> procesada-------------------<br><br>';
	$last_id_table++;
	
	///
	
	$columns_array = seleccionar( $con_local, $tablename2, $expression2, $tablename, $tableid );
	//diferenciarAvd( $con_local, $enlace_nombre, $tablename2, $expression2, $tablename, $tableid );
	foreach ($columns_array as $column)
	{
		$insert_q = dameElInsertParcialDeLaTabla( $con_local, $tablename2 );
		$tarray = listarTiposDeTabla( $con_local, $tablename2 );
		
		// se buscan los datos completos de la fila
		$col_values_array = findByExpression_col( $con_local, $tablename2, $expression2, $column, $tablename, $tableid ); 
				
		//print_r($col_values_array);
		
		// guardar en tabla temporal	
		$col_values_array[0] = 0; 
		$col_values_array[3] = 0;	// AD_Org_ID
		$col_values_array[49] = 1;	// Version
		$columnid  = $col_values_array[1]; // guardar el id original
		$columnname2 = $col_values_array[10];
		$elemid	 = $col_values_array[2]; // AD_Element_ID
		$tableid = $col_values_array[7]; // AD_Table_ID
		$refid   = $col_values_array[6]; // AD_Reference_ID
		$vruleid = $col_values_array[8]; // AD_Val_Rule_ID
		$insertar_tmp_q_total = " $insert_tmp_q VALUES ( $columnid, $last_id_table2, $columnname2, '$tablename2' ) ";
		//echo "<br/><br/> $insertar_tmp_q_total <br/><br/>";
		$stmt3 = oci_parse( $con_local, $insertar_tmp_q_total );
		oci_execute( $stmt3 );
		
		//buscar el id table que le corresponde dado el viejo
		$new_elemid  = getIDByOldID( $con_local, $enlace_nombre, 'AD_ELEMENT', 	$elemid );
		$new_tableid = getIDByOldID( $con_local, $enlace_nombre, 'AD_TABLE', 	$tableid );
		$new_refid 	 = getIDByOldID( $con_local, $enlace_nombre, 'AD_REFERENCE', $refid );
		$new_vruleid = getIDByOldID( $con_local, $enlace_nombre, 'AD_VAL_RULE', $vruleid );
		
		// se prepara consulta de migracion con id nuevo
		$col_values_array[1] = $last_id_table2; // actualizo al ultimo id
		if ( $new_elemid != 0 )
			$col_values_array[2] = $new_elemid;
		if ( $new_tableid != 0 )
			$col_values_array[7] = $new_tableid;
		if ( $new_refid != 0 )
			$col_values_array[6] = $new_refid;
		// falta vrule
		
		$iquery = $insert_q . ' VALUES (' . implode(",", $col_values_array) . ')';
		echo $iquery. '<br><br>';
		$stmt28 = oci_parse( $c2, $iquery );
		oci_execute( $stmt28 );
		echo '<b>' . $columnname2 . '</b> procesada<br><br>';
		$last_id_table2++;
		
	} // end foreach AD_Column

} // end foreach AD_Table

oci_close($con_local);
oci_close($c2);
?>

<form method="post" action="<?php echo "./index.php"; ?>">
   <input type="submit" value="Ir atras" /> 
</form>

 </body>
</html>