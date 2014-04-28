<?php 
   include '../../utils.php';
   session_start();
   
   $tablename  = 'M_PRICELIST';
   
   $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
   $rows = isset($_POST['rows']) ? intval($_POST['rows']) : 15;
   $offset = ($page-1)*$rows;
   
   $json_rs = cDiferenciarEstructuraEnJSON( $tablename, $page, $rows, $offset );
   echo $json_rs;
	
?>