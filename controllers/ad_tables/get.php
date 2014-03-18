<?php 
include '../../utils.php';

session_start();

$page = isset($_POST['page']) ? intval($_POST['page']) : 1;
$rows = isset($_POST['rows']) ? intval($_POST['rows']) : 10;
$offset = ($page-1)*$rows;

$tablename  = 'AD_TABLE';
$expression = 'UPPER(TABLENAME)';

$json_rs = cDiferenciarEnJSON( $tablename, $expression, $page, $rows, $offset );
echo $json_rs;
?>