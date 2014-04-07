<?php 
include_once '../../utils.php';
include_once 'DataHandler.php';
include_once 'AdRole.php';

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
	public function cMigrateByName( $name, $last_id_win, $save_changes = true )
	{
		$last_id_entity = $last_id_win;
		$entity_name = $name;	

		$role_array = MRole::cFindAllWhereNoManual();
		foreach ( $role_array  as $element )
		{
			$wa->cPut( $element['AD_Role_ID'] );
			//$wa.save();
		}
		
		
		return $last_id_entity;
		
	} // end cMigrateByName

} // end class

?>