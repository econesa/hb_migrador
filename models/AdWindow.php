<?php 
include_once '../../utils.php';
include_once 'DataHandler.php';
include_once 'AdWindowAccess.php';
//session_start();


class AdWindow extends DataHandler
{
	const  TABLENAME  = 'AD_WINDOW';
	
	public function getTablename( )
	{
		return self::TABLENAME;
	}

	public function __construct()
	{
		parent::load();
		$this->parent_tablename  = '';
		$this->tablename  = 'AD_WINDOW';
		$this->expression = 'UPPER(T.NAME)';
	}

	public function getExpression( )
	{
		return $this->expression;
	}
	
	function cFindByExpression( $value )
	{
		$values_array = array();
		$query = " SELECT * FROM $this->tablename t WHERE $this->expression LIKE '$value' ";
		//echo "<br> $query <br>";

		$connection = oci_connect( $this->username_s, $this->password_s, $this->path_s );
		
		$tarray = listarTiposDeTabla( $connection, $this->tablename );
		
		$stmt   = oci_parse( $connection, $query );
				
		if ( oci_execute( $stmt ) )
		{
			$values_array = oci_fetch_assoc( $stmt ); 
			if ( !empty($values_array) )
			{
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
		}

		oci_close($connection);

		return $values_array;
	} // end cFindByExpression

	/*  */
	public function cMigrateByPK( $pk_id, $save_changes = true )
	{
		$entity_name = $this->cFindNameBySPK( $pk_id );
		$last_id_entity = 0;
		if ( !empty($entity_name) )
		{
			$last_id_entity = $this->cMigrateByName( $entity_name, $this->cLastID() + 1, $save_changes );
		}
		
		return $last_id_entity;	
	} // end cMigrateByPK

	public function setNulls( $values_array )
	{
		$v_array = $values_array;

		if ( empty($v_array['AD_CTXAREA_ID']) 	) 	$v_array['AD_CTXAREA_ID'] = 'NULL';
		if ( empty($v_array['AD_IMAGE_ID']) 	) 	$v_array['AD_IMAGE_ID']   = 'NULL';
		if ( empty($v_array['AD_COLOR_ID']) 	)	$v_array['AD_COLOR_ID']   = 'NULL';
		if ( empty($v_array['DESCRIPTION']) 	) 	$v_array['DESCRIPTION']   = 'NULL';
		if ( empty($v_array['HELP']) 			) 	$v_array['HELP']   	   	  = 'NULL'; 
		if ( empty($v_array['ISCUSTOMDEFAULT']) )	$v_array['ISCUSTOMDEFAULT']  = 'NULL';
		if ( empty($v_array['PROCESSING']) 		)	$v_array['PROCESSING']    = 'NULL';

		return $v_array;
	}

	public function cMigrateByName( $name, $last_id_win, $save_changes = true )
	{
		$last_id_entity = $last_id_win;
		$entity_name = $name;	
		
		$exists = $this->cCountByExpression( $entity_name ); 
		if ($exists == 0) 
		{
			$values_array = $this->cFindByExpression( $entity_name );
			if ( $values_array[ $this->tablename . '_ID' ] >= 5000000 )
			{
				echo "<br> migrando ventana $entity_name.... <br>";
				$values_array = $this->setNulls( $values_array );
				$values_array[ $this->tablename . '_ID' ] = $last_id_entity;

				unset( $values_array[ 'AD_USER_ID' ] );
				$this->cPut( $values_array, $save_changes );

				$win_access_obj = new AdWindowAccess();
				$win_access_obj->cGenerate( $values_array[ $this->tablename . '_ID' ], $values_array[ 'CREATEDBY' ], $save_changes );

			}
			else
			{
				echo "<br/> La ventana es original de compiere. <br/>";
			}
		}
		else
		{
			$values_array   = $this->cFindByExpression( $entity_name, false );
			$last_id_entity = $values_array[ $this->tablename . '_ID' ];
			echo "<br> AD_WIN :: existe $entity_name con ID:$last_id_entity <br>";
		}

		return $last_id_entity;
		
	} // end cMigrateByName

	/* Migra una ventana dado su nombre y el id que debe tener.
	public function cMigrate( $values_array, $table_id, $save_changes = true )
	{
		//$tmp_obj = new TAdMig( $save_changes );
		//$tmp_obj->truncate();
		
		//$id_old    = $values_array['AD_WINDOW_ID'];

		echo "<br>** migrando ventana {$values_array['NAME']}.... **<br>";
		/*
		$seq_obj   = new AdSequence(); // TODO: verificar si la secuencia ya existe
		$seq_array = $seq_obj->cFindByTablename( $values_array['NAME'], $save_changes );
		$seq_array['AD_SEQUENCE_ID'] = $seq_obj->cLastID( ) + 1;
		$seq_obj->cPut( $seq_array, $save_changes );
		
		//$tmp_obj->cPut( $id_old, $table_id, $values_array['NAME'], $this->tablename );

		// se prepara consulta de migracion con id nuevo
		$values_array['AD_CLIENT_ID'] = $values_array['AD_ORG_ID'] = 0;
		$values_array['AD_IMAGE_ID']  = $values_array['AD_CTXAREA_ID'] = $values_array['AD_COLOR_ID']  = 'NULL';
		$values_array['AD_WINDOW_ID'] = $table_id; // actualizo al ultimo id
		//echo '<br/>'; print_r( $values_array ); echo '<br/>';

		$this->cPut( $values_array, $save_changes );	
		//$this->cPut( $this->prepareValues($values_array));
	}
	*/
} // end class

?>