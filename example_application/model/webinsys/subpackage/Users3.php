<?php
PhpBURN::import('webinsys.subpackage.Users2');
class Users3 extends Users2 {
	public $_package = 'webinsys.subpackage';
	public $_tablename = 'users3';
	
	public $id;
	public $second_name;
	
	/**
	 * Here we setup all mapping fields without user XML
	 * 
	 * IMPORTANT: This method will ONLY be called automaticaly if the model DOES NOT have a xml Map
	 * 
	 * @example $this->_mapObj->addField('name','column','sqlType','length',array('notnull' => true, 'autoincrement' => true);
	 * @example $this->_mapObj->addField('name','column','sqlType','length',array();
	 * @example $this->_mapObj->addField('id','user_id','int','10',array('notnull' => true, 'pk' => true, 'autoincrement' => true);
	 */
	public function _mapping() {
		$this->_mapObj->addField('id', 'id', 'int', '50',array('autoincrement' => true, 'notnull' => true));
		$this->_mapObj->addField('second_name', 'name', 'varchar', '255',array('notnull' => true, 'defaultvalue' => 'your name here'));
	}
}
?>