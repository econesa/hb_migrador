<?php 
include_once '../../utils.php';
include_once 'DataHandler.php';

//session_start();


class AdWindowAccess extends DataHandler
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

	/**/
	public function cGenerate( $p_win_id, $p_user_id, $save_changes = true )
	{
		$win_id  = $p_win_id;
		$user_id = $p_user_id;
		$fecha   = date('d/m/y');	

		// 5000100 es el role Hierrobeco Perfil Administrador
		// ISREADWRITE debe estar en Y para que se puedan crear registros.
		$query   = "Insert into AD_WINDOW_ACCESS (AD_CLIENT_ID,AD_ORG_ID,AD_ROLE_ID,AD_WINDOW_ID,CREATED,CREATEDBY,ISACTIVE,ISREADWRITE,UPDATED,UPDATEDBY) 
		 			values ('0','0','5000100','$win_id',to_date('$fecha','DD/MM/RR'),'5008705','Y','Y',to_date('$fecha','DD/MM/RR'),'$user_id')";
		echo "<br> $query <br>";
		
		try 
		{
			$connection = oci_connect( $this->username_d, $this->password_d, $this->path_d );
			$stmt = oci_parse( $connection, $query );

			if ( $save_changes )
			{
				if ( oci_execute( $stmt ) )
				{
					echo "<br> insertado <br/>"; 
				}
				else
				{
					$e = oci_error( $stmt ); 
					echo $e['message'] . '<br/>'; 
				}	
			}			
			
			oci_close( $connection );		
		} 
		catch( Exception $exc )
		{
			echo "Exception: $exc->getMessage() <br>";
		}
		
	} // end cMigrateByName

} // end class

?>