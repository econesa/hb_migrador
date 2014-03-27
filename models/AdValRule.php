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
		$tablename    = self::TABLENAME;
		$connection   = null;

		if ( $extern )
		{
			$username   = $_SESSION['user_origen'];
			$password   = $_SESSION['user_opw'];
			$connection = oci_connect( $username, $password, $_SESSION['ip_origen'] . '/XE' );
		}
		else
		{
			$username   = $_SESSION['user_destino'];
			$password   = $_SESSION['user_dpw'];
			$connection = oci_connect( $username, $password, $_SESSION['ip_destino'] . '/XE' );
		}

		$query  = " SELECT * FROM {$this->tablename} t WHERE $this->expression LIKE '$value' ";
		//echo "<br> $query <br/>";

		$tarray = listarTiposDeTabla( $connection, self::TABLENAME );
		//print_r($value);

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
		$referencev_name = $name;
		$last_id_refv = $last_id;

		// verificar si el reference esta en el origen, en cuyo caso se migra.
		$exists = $this->cCountByExpression( $referencev_name ); 
		if ( $exists == 0 ) 
		{ 
			$refv_values_array = $this->cFindByExpression( $referencev_name );
			if ( $refv_values_array['AD_VAL_RULE_ID'] >= 5000000 )
			{
				//echo " elemento extendido <br/>";
				$refv_values_array['AD_VAL_RULE_ID'] = $last_id_refv;
				$this->cPut( $refv_values_array, $save_changes );
				$last_id_refv++;
			}
		}
		else
		{			
			$values_array = $this->cFindByExpression( $referencev_name, false );
			$last_id_refv = $values_array['AD_VAL_RULE_ID'];
			echo "<br> existe $referencev_name con ID:$last_id_refv <br>";
		}
		return $last_id_refv;

	} // end cMigrateByName

	/**/
	public function cMigrateByParentId( $parent_id, $save_changes = true )
	{
		$last_id_refv = $this->cLastID() + 1; 
		$lista    = $this->cFindAllByParentId( $parent_id ); 
		foreach ($lista as $referencev_name) 
		{ 
			$this-> cMigrateByName( $referencev_name, $last_id_refv, $save_changes );
		} 

	} // end cMigrate

} // end class
?>