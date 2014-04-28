<?php 
include_once 'DataHandler.php';


class AdValRule extends DataHandler
{
	const TABLENAME  = 'AD_VAL_RULE';
	public $expression = 'UPPER(T.NAME)';
	
	/**/
	public function getTablename( )
	{
		return $this->tablename;
	}

	public function __construct()
	{
		parent::load();
		$this->parent_tablename = 'AD_TABLE';
		$this->tablename  = 'AD_VAL_RULE';
		$this->expression = 'UPPER(T.NAME)';
	}

	/**/
	public function cFindByExpression( $value, $extern = true )
	{
		$values_array = array();
		$query  = " SELECT * FROM {$this->tablename} t WHERE $this->expression LIKE '$value' ";
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

		$tarray = listarTiposDeTabla( $connection, self::TABLENAME );

		$stmt = oci_parse( $connection, $query );
		
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

		} // execute 
		else
		{
			$e = oci_error( $stmt ); 
	        echo $e['message']; 
		}
	
		oci_close($connection);
		
		return $values_array;
	}

	public function cMigrateByPK( $pk_id, $save_changes = true )
	{
		$entity_name = $this->cFindNameBySPK( $pk_id );
		$last_id_entity = $this->cLastID() + 1;
		echo "<br> {$this->tablename} :: migrando val rule $entity_name.... <br>";
		$last_id_entity = $this->cMigrateByName( $entity_name, $last_id_entity, $save_changes );	
		return $last_id_entity;		
	} // end cMigrateByPK

	/**/
	public function cMigrateByName( $name, $last_id, $save_changes = true )
	{
		$entity_name = $name;
		$last_id_entity = $last_id;

		// verificar si el reference esta en el origen, en cuyo caso se migra.
		$exists = $this->cCountByExpression( $entity_name ); 
		if ( $exists == 0 ) 
		{ 
			$values_array = $this->cFindByExpression( $entity_name );
			if ( $values_array['AD_VAL_RULE_ID'] >= 5000000 )
			{
				//echo " elemento extendido <br/>";
				$values_array['AD_VAL_RULE_ID'] = $last_id_entity;
				unset( $values_array[ 'AD_USER_ID' ] ); 
				$this->cPut( $values_array, $save_changes );
			}
			else
			{
				echo "<br> Es del compiere original <br>";
			}
		}
		else
		{			
			$values_array = $this->cFindByExpression( $entity_name, false );
			$last_id_entity = $values_array['AD_VAL_RULE_ID'];
			echo "<br> existe $entity_name con ID:$last_id_entity <br>";
		}
		return $last_id_entity;

	} // end cMigrateByName

	/**/
	public function cMigrateByParentId( $parent_id, $save_changes = true )
	{
		$last_id_entity = $this->cLastID() + 1; 
		$lista    = $this->cFindAllByParentId( $parent_id ); 
		foreach ($lista as $entity_name) 
		{ 
			$this-> cMigrateByName( $entity_name, $last_id_entity, $save_changes );
		} 

	} // end cMigrateByParentId

} // end class
?>