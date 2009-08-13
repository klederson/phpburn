<?php
class Users extends PhpBURN_Core {
	public $_package = 'webinsys';
	public $_tablename = 'users';
	
	public $id_user;
	public $name;
	public $password;
	public $created_at;
	
	public function _mapping() {
		$this->getMap()->addField('id_user','id_user','int',10,array('autoincrement' => true, 'primary' => true));
		$this->getMap()->addField('name','name','varchar',255, array() );
		$this->getMap()->addField('password','password','varchar',255, array() );
		$this->getMap()->addField('created_at','created_at','timestamp', null, array('default_value' => 'CURRENT_TIMESTAMP') );
	}
}
?>