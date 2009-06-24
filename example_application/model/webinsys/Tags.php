<?php
class Tags extends PhpBURN_Core {
	public $_package = 'webinsys';
	public $_tablename = 'tags';
	
	public $id_tags;
	public $name;
	public $enabled;
	
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
		$this->_mapObj->addField('id_tags', 'id_tags', 'int', '10',array('primary' => true, 'autoincrement' => true, 'notnull' => true));
		$this->_mapObj->addField('name', 'name', 'varchar', '255',array('notnull' => true));
		$this->_mapObj->addField('enabled', 'enabled', 'enum', '1,0' ,array('notnull' => true, 'default-value' => 'CURRENT_TIMESTAMP'));
	}
}
?>