<?php
class Teste2 extends Teste {
	public $_package = 'webinsys.subpackage';
	public $_tablename = 'users2';
	
	public $id;
	public $name;
	
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
		$this->_mapObj->addField('name', 'name', 'varchar', '255',array('notnull' => true, 'defaultvalue' => 'your name here'));
	}
}
?>