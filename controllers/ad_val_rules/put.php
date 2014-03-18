<?php
/**
** AD_VAL_RULE
***/

include '../../models/AdValRule.php';
include '../../models/TAdMig.php';
include '../../utils.php';

session_start(); 

$data = json_decode( file_get_contents('php://input'), true );

$tablename  = 'AD_VAL_RULE';
$expression = 'UPPER(NAME)';
//$last_id_table = getLastIdTable( $con_local, $enlace_nombre, $tablename )+1;

$val_rule_obj  = new AdValRule();
$tmp_obj   = new TAdMig();

$last_id_val_rule = $val_rule_obj->cLastID() + 1;


foreach ($data as $item)
{
	// se buscan los datos completos de la fila
	$values_array = $val_rule_obj->cFindByExpression( $item[$expression] );
	
	// Put ( id_original, id_new, value, tablename )
	$tmp_obj->cPut( $values_array['AD_VAL_RULE_ID'], $last_id_val_rule, $values_array['NAME'], $val_rule_obj->getTablename( ) );
		
	// se prepara consulta de migracion con id nuevo
	$values_array['AD_VAL_RULE_ID'] = $last_id_val_rule; // actualizo al ultimo id
	$val_rule_obj->cPut( $values_array );	

	$last_id_val_rule++; 
		
} // end foreach AD_Val_Rule

?>