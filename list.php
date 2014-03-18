<html> 
 <head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"> 
  <title>Migrador</title> 
  <link rel="stylesheet" type="text/css" href="./easyui/themes/default/easyui.css">
  <link rel="stylesheet" type="text/css" href="./easyui/themes/icon.css">
  <link rel="stylesheet" type="text/css" href="./easyui/demo/demo.css">
  <script type="text/javascript" src="./easyui/jquery.min.js"></script>
  <script type="text/javascript" src="./easyui/jquery.easyui.min.js"></script>
  <script type="text/javascript" src="./easyui/easyloader.js"></script>  
  <script type="text/javascript">
	function procesar( table_id, tablename )
	{
		var row = $(table_id).datagrid('getSelections');
		if (row)
		{
			var localJSONData = JSON.stringify(row);
			
			$.ajax(
			{
				type: 'POST',	dataType: 'json',
				url: './controllers/' + tablename + '/put.php',				
				data: localJSONData,
				contentType: "application/json; charset=utf-8"
			});

			$(table_id).datagrid('reload');
		}		
	}
  </script>
 </head>
 <body> 

 	<?php
	//include 'utils.php';

	session_start(); 

	// cargar sesion
	if ( empty($_SESSION['ip_origen']) || empty($_SESSION['ip_destino']) )
	{
		if ( empty($_POST['ip_origen']) || empty($_POST['ip_destino']) )
		{
			echo "wrong ips";
			exit;
		}
		
		$_SESSION['ip_origen']  = $_POST['ip_origen'];
		$_SESSION['user_origen'] = 'compiere';
		$_SESSION['user_opw'] 	 = 'compiere';
		
		$_SESSION['ip_destino'] = $_POST['ip_destino'];
		$_SESSION['user_destino'] = 'compiere';
		$_SESSION['user_dpw'] 	  = 'compiere'; //'oracle'//
	}

	$tablename  = 'AD_ELEMENT';
	$expression = 'UPPER(COLUMNNAME)';
	$foldername = 'ad_elements';
	$component_id = 'tt';
	?>

	<form method="post" action="<?php echo "./cerrar.php"; ?>">
	  <input type="submit" value="Cerrar Sesion" />
	</form>

<h3><?php echo $tablename; ?></h3>
<table 	id="<?php echo $component_id; ?>" class="easyui-datagrid" style="width:auto; height:auto;"
		rownumbers="true" toolbar="#toolbarElem" 	url="./controllers/<?php echo $foldername; ?>/get.php" pagination="true" >
  <thead>
	 <tr>
	   <th field="<?php echo $expression; ?>"><?php echo $expression; ?></th>         
	 </tr>                          
  </thead>	  
</table>
   
<div id="toolbarElem">
    <a href="#" class="easyui-linkbutton" iconCls="icon-add" plain="true" onclick="procesar('#<?php echo $component_id; ?>', '<?php echo $foldername; ?>')" >Migrar</a>
</div>
<?php

$tablename  = 'AD_REFERENCE';
$expression = 'UPPER(NAME)';
$foldername = 'ad_references';
$component_id = 'tt3';
?>
<h3><?php echo $tablename; ?></h3>
<table 	id="<?php echo $component_id; ?>" class="easyui-datagrid" style="width:auto; height:auto;"
		rownumbers="true" toolbar="#toolbarRef" 	url="./controllers/<?php echo $foldername; ?>/get.php" pagination="true" >
  <thead>
	 <tr>
	   <th field="<?php echo $expression; ?>"><?php echo $expression; ?></th>         
	 </tr>                          
  </thead>	  
</table>
   
<div id="toolbarRef">
    <a href="#" class="easyui-linkbutton" iconCls="icon-add" plain="true" onclick="procesar('#<?php echo $component_id; ?>', '<?php echo $foldername; ?>')" >Migrar</a>
</div>

<?php
//-------------------------------------------//
$tablename  = 'AD_VAL_RULE';
$expression = 'UPPER(NAME)';
$foldername = 'ad_val_rules';
$component_id = 'tableVR';
?>
<h3><?php echo $tablename; ?></h3>
<table 	id="<?php echo $component_id; ?>" class="easyui-datagrid" style="width:auto; height:auto;"
		rownumbers="true" toolbar="#toolbarVal" 	url="./controllers/<?php echo $foldername; ?>/get.php" pagination="true" >
  <thead>
	 <tr>
	   <th field="<?php echo $expression; ?>"><?php echo $expression; ?></th>         
	 </tr>                          
  </thead>	  
</table>
   
<div id="toolbarVal">
    <a href="#" class="easyui-linkbutton" iconCls="icon-add" plain="true" onclick="procesar('#<?php echo $component_id; ?>', '<?php echo $foldername; ?>')" >Migrar</a>
</div>
<?php
//-------------------------------------------//
$tablename  = 'AD_TABLE';
$expression = 'UPPER(TABLENAME)';
$foldername = 'ad_tables';
$component_id = 'tt2';
?>
<h3><?php echo $tablename; ?></h3>
<table 	id="<?php echo $component_id; ?>" class="easyui-datagrid" style="width:auto;height:auto;" url="./controllers/<?php echo $foldername; ?>/get.php"
		rownumbers="true" toolbar="#toolbar2" >
  <thead>
	 <tr>
	   <th field="<?php echo $expression; ?>"><?php echo $expression; ?></th>         
	 </tr>                          
  </thead>                                                     
</table>
<div id="toolbar2">
    <a href="#" class="easyui-linkbutton" iconCls="icon-add" plain="true" onclick="procesar('#<?php echo $component_id; ?>', '<?php echo $foldername; ?>')">Migrar</a>
</div>
<?php

//-------------------------------------------//
$tablename    = 'AD_WINDOW';
$expression   = 'UPPER(NAME)';
$foldername   = 'ad_windows';
$component_id = 'tt4';
?>
<h3><?php echo $tablename; ?></h3>
<table 	id="<?php echo $component_id; ?>" class="easyui-datagrid" style="width:auto;height:auto;" url="./controllers/<?php echo $foldername; ?>/get.php"
		rownumbers="true" toolbar="#toolbarWin" >
  <thead>
	 <tr>
	   <th field="<?php echo 'UPPER(NAME)'; ?>"><?php echo 'UPPER(NAME)'; ?></th>         
	 </tr>                          
  </thead>                                                     
</table>
<div id="toolbarWin">
    <a href="#" class="easyui-linkbutton" iconCls="icon-add" plain="true" onclick="procesar('#<?php echo $component_id; ?>', '<?php echo $foldername; ?>')">Migrar</a>
</div>
<?php
//-------------------------------------------//
?>

 </body>
</html>