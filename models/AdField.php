<?php 
//include '../utils.php';

//session_start();

class AdField extends DataHandler
{
	const PARENT_TABLENAME  = 'AD_TAB'; // ojo falta 
	const TABLENAME         = 'AD_FIELD'; // ojo falta
	
	public function getTablename( )
	{
		return $this->tablename;
	}
	
	public function getExpression( )
	{
		return $this->expression;
	}

	public function __construct()
	{
		parent::load();
		$this->parent_tablename = 'AD_TAB';
		$this->tablename  = 'AD_FIELD';
		$this->expression = 'UPPER(T.NAME)';
	}

	public function cFindByParentID( $parent_id )
	{
		$result = array();
		
		$connection = oci_connect( $this->username_s, $this->password_s, $this->path_s );
		
		$query = " SELECT {$this->expression} 		
			  	   FROM   COMPIERE.{$this->tablename} t 
			  	   WHERE  {$this->parent_tablename}_ID = {$parent_id} ";
		echo "<br/> $query <br/>";

		$stmt = oci_parse( $connection, $query );
		if ( oci_execute( $stmt ) )
		{}
		else{ 
			$e = oci_error($stmt); 
			echo $e['message'] . '<br/>'; 
		}
		$nrows = oci_fetch_all($stmt, $res);

		oci_close($connection);
		
		return $res[$this->expression];
	}

	function cFindByExpression( $value, $parentID )
	{
		$values_array = array();

		$connection = oci_connect( $this->username_s, $this->password_s, $this->path_s );

		$tarray = listarTiposDeTabla( $connection, $this->tablename );

		$query  = " SELECT * FROM $this->tablename t 
					WHERE $this->expression LIKE '$value' AND {$this->parent_tablename}_ID = $parentID ";
		echo "<br> $query <br>";
		
		$stmt  = oci_parse( $connection, $query );
		if ( oci_execute( $stmt ) )
		{
			$values_array = oci_fetch_assoc( $stmt ); 
			$i = 0;

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
		oci_close($connection);

		return $values_array;
	}

	/*  */
	public function cMigrateByPK( $pk_id, $save_changes = true )
	{
		$entity_name = $this->cFindNameBySPK( $pk_id );
		$last_id_entity = $this->cMigrateByName( $entity_name, $this->cLastID() + 1, $save_changes );
		return $last_id_entity;	
	} // end cMigrateByPK

	/*  */
	public function cMigrateByName( $name, $last_id, $save_changes = true )
	{
		$entity_name = $name;
		$last_id_entity = $last_id;

		// TODO
	}

} // end class

?>