<?php 
include_once '../../utils.php';
include_once 'DataHandler.php';
include_once 'AdWindow.php';
include_once 'AdTree.php';

//session_start();

class AdMenu extends DataHandler
{
	const TABLENAME  =  'AD_MENU';

	public function __construct()
	{
		parent::load();
		$this->tablename  = 'AD_MENU';
		$this->expression = 'UPPER(T.NAME)'; // el indice asociativo esta dado en mayusculas
	}

	public function getTablename()
	{
		return $this->tablename;
	}
	
	public function cGet( $parent_id ) // ver si se utiliza aun 
	{
		$username   = $_SESSION['user_origen'];
		$password   = $_SESSION['user_opw'];
		$connection = oci_connect( $username, $password, $_SESSION['ip_origen'] . '/XE' );
		
		$mip   = explode( ".", $_SESSION['ip_destino'] );
		$dblinkname = 'HBE_DESA_' . $mip[3]; //Database Link
		
		$query = " SELECT UPPER(t.NAME) 
				   FROM   COMPIERE.{$this->tablename} t 
				   JOIN   AD_COLUMN columna ON (columna.{$this->tablename}_ID = t.{$this->tablename}_ID) 
				   WHERE  columna.AD_TABLE_ID = $parent_id 
				     MINUS 
				   SELECT UPPER(t2.NAME) 
				   FROM   COMPIERE.{$this->tablename}@{$dblinkname} t2 ";
		
		$stmt = oci_parse( $connection, $query );
		if ( oci_execute( $stmt ) )
		{
			$rs = oci_fetch_row($stmt);
		}
		
		//oci_free_statement($stmt);
		oci_close( $connection );
		
		return $rs;
	}

	/**/
	public function cFindByExpression( $value, $extern = true )
	{
		$values_array = array();
		$query  = " SELECT * FROM $this->tablename t WHERE $this->expression LIKE '$value' ";
		//echo "<br> $query <br/>";

		$connection   = null;

		if ( $extern )
		{
			$connection = oci_connect( $this->username_s, $this->password_s, $this->path_s );
		}
		else
		{
			$connection = oci_connect( $this->username_d, $this->password_d, $this->path_d );
		}

		$tarray = listarTiposDeTabla( $connection, $this->tablename );
		
		$stmt = oci_parse( $connection, $query );
		
		if ( oci_execute( $stmt ) )
		{
			$values_array = oci_fetch_assoc( $stmt ); 
			$i = 0;
			if ( !empty($values_array) )
			{
				foreach ( $values_array as $indice => $field )
				{
					if ( empty($field) )
					{
						$values_array[$indice] = formatEmpty( $tarray[$i]['tipo'], $field );
					}
					else
					{
						$values_array[$indice] = formatData( $tarray[$i]['tipo'], $field );
					}
					$i++;
				}
			}
		} // execute 
		else
		{
			$e = oci_error( $stmt ); 
	        echo $e['message']; 
		}
	
		oci_close($connection);
		
		return $values_array;
	}

	/*public function cMigrateByPK( $pk_id, $save_changes = true )
	{
		$entity_name    = $this->cFindNameBySPK( $pk_id );
		$last_id_entity = $this->cMigrateByName( $entity_name, $this->cLastID() + 1, $save_changes );
		return $last_id_entity;	
	} // end cMigrateByPK
	*/

	public function cMigrateByName( $name, $last_id, $save_changes = true )
	{
		$entity_name = $name;
		$last_id_entity = $last_id;

		echo "<br> {$this->tablename} :: migrando Menu $entity_name.... <br>";

		$exists = $this->cCountByExpression( $entity_name ); 		
		if ($exists == 0) 
		{
			// se buscan los datos completos de la fila
			$values_array = $this->cFindByExpression( $entity_name );
			
			$old_id = $values_array[ $this->tablename . '_ID' ];

			if ( $values_array['AD_MENU_ID'] >= 5000000 )
			{				
				// se prepara consulta de migracion con id nuevo
				$values_array['AD_MENU_ID'] = $last_id_entity;
				
				if ( $values_array['AD_FORM_ID'] == 0 )
					$values_array['AD_FORM_ID'] = 'NULL';
				if ( $values_array['AD_PROCESS_ID'] == 0 ) 
					$values_array['AD_PROCESS_ID'] = 'NULL';
				if ( $values_array['AD_TASK_ID'] == 0 )
					$values_array['AD_TASK_ID'] = 'NULL';
				if ( $values_array['AD_WORKBENCH_ID'] == 0 )
					$values_array['AD_WORKBENCH_ID'] = 'NULL';	 
				if ( $values_array['AD_WORKFLOW_ID'] == 0 )
					$values_array['AD_WORKFLOW_ID'] = 'NULL';
				//********buscar el valor de la Ad_Window. La ventana tiene que existir en el destino. FK 116_233

				$win_obj  = new AdWindow();
				//echo "<br>*+*+*+ verificando ventana {$values_array['AD_WINDOW_ID']} <br> ";
				echo "*+*+*+";
				$values_array['AD_WINDOW_ID'] = $win_obj->cMigrateByPK( $values_array['AD_WINDOW_ID'], $save_changes );
				echo "*+*+*+";

				echo " menu extendido - $last_id_entity <br/>";
				$this->cPut( $values_array, $save_changes );


				//Buscar los datos completos del arbol. (Actualmente esta buscando una sola fila de la tabla). 
				//OJO: Se necesitan buscar todas las filas de la tabla que contengan el NODE_ID.

				$treeMM_obj   = new AdTreeNodeMM();	
				$values_arrayTreeMM = $treeMM_obj->cFindAllTree( $old_id ); //Busca en el origen los datos de la fila
				foreach ( $values_arrayTreeMM as $tree_values_array ) 
				{
					//Se prepara consulta de migracion con id nuevo
					$tree_values_array['NODE_ID'] = $values_array['AD_MENU_ID'];
					//********buscar el valor de la Ad_Tree_ID. El arbol (tree) tiene que existir en el destino.
					
					$treeMM_obj->cPut( $tree_values_array, $save_changes );

				}
				
				/*
				echo "<br> --------------";
				print_r($values_arrayTreeMM);
				echo "--------------<br> ";
				*/

			}
			else
			{
				$values_array   = $this->cFindByExpression( $entity_name, false );
				$last_id_entity = $values_array['AD_MENU_ID'];
				echo "<br> El menu ya esta en compiere base - $entity_name con ID:$last_id_entity <br>";
			}
		}
		else
		{			
			$values_array = $this->cFindByExpression( $entity_name, false );
			$last_id_entity = $values_array['AD_MENU_ID'];
			echo "<br> existe $entity_name con ID:$last_id_entity <br>";
		}	
		return $last_id_entity;

	} // end cMigrateByName

	

} // end class
?>