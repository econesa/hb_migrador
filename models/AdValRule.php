<?php 
include_once 'DataHandler.php';

class AdValRule extends DataHandler
{
	const TABLENAME  = 'AD_VAL_RULE';
	public $expression = 'UPPER(T.NAME)';
	
	/**/
	public function getTablename( )
	{
		return self::TABLENAME;
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

	/**/
	public function cMigrateByName( $name, $last_id, $save_changes = true )
	{
		$referencev_name = $name;
		$last_id_refv = $last_id;

		// verificar si el reference esta en el origen, en cuyo caso se migra.
		$refv_exists = $this->cCountByExpression( $referencev_name ); 
		//echo " $refv_exists <br/>";
		if ($refv_exists == 0) 
		{ 
			$refv_values_array = $this->cFindByExpression( $referencev_name );
			if ( $refv_values_array['AD_VAL_RULE_ID'] >= 5000000 )
			{
				echo " elemento extendido <br/>";
				$refv_values_array['AD_VAL_RULE_ID'] = $last_id_refv;
				$this->cPut( $refv_values_array, $save_changes );
				$last_id_refv++;
			}
		}
		else
		{
			/*$refv_values_array  = $this->cFindByExpression( $referencev_name, false );
			$refvo_values_array = $this->cFindByExpression( $referencev_name );
			if ( $refv_values_array['AD_VAL_RULE_ID'] >= 5000000 )
			{
				echo " elemento extendido <br/> ";
				//$tmp_obj->cPut( $refvo_values_array['AD_VAL_RULE_ID'], $refv_values_array['AD_VAL_RULE_ID'], $refv_values_array['NAME'], $this->tablename );
			}*/
		}		

	} // end cMigrateByName

	/**/
	public function cMigrateByParentId( $parent_id, $save_changes = true )
	{
		$id_old = $parent_id;
		$tmp_obj = new TAdMig( $save_changes );

		$last_id_refv = $this->cLastID() + 1; 
		$lista    = $this->cFindAllByParentId( $id_old ); 
		foreach ($lista as $referencev_name) 
		{ 
			$this-> cMigrateByName( $referencev_name, $last_id_refv, $save_changes );
		} 

	} // end cMigrate

} // end class
?>