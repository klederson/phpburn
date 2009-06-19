<?php
class Albums extends PhpBURN_Core {
	public $_package = 'webinsys';
	public $_tablename = 'albums';
	
	public $id;
	public $user_id;
	
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
		$this->_mapObj->addField('id', 'id', 'int', '10',array('primary' => true, 'autoincrement' => true, 'notnull' => true));
		$this->_mapObj->addField('user_id', 'user_id', 'int', '10',array('notnull' => true));
	}
}
?>