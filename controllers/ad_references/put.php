<?php
include '../../models/AdReference.php';
include '../../models/TAdMig.php';
include '../../utils.php';

session_start(); 

$data = json_decode( file_get_contents('php://input'), true );

// 'AD_REFERENCE';

$ref_obj   = new AdReference();
$tmp_obj   = new TAdMig();

$ref_obj->load();

$last_id_ref = $ref_obj->cLastID() + 1;

foreach ($data as $item)
{
	// echo $item[$expression];

	// se buscan los datos completos de la fila
	$values_array = $ref_obj->cFindByExpression( $item['UPPER(NAME)'] );
	//print_r($values_array);

	if( !empty($values_array) )
	{
		// Put ( id_original, id_new, value, tablename )
		$id_old = $values_array['AD_REFERENCE_ID'];
		$id_new = $last_id_ref;
		$tmp_obj->cPut( $id_old, $id_new, $values_array['NAME'], $ref_obj->getTablename( ) );

		// se prepara consulta de migracion con id nuevo
		$values_array['AD_REFERENCE_ID'] = $id_new; // actualizo al ultimo id
		$ref_obj->cPut( $values_array );

		$last_id_ref++;
	}
}

?>