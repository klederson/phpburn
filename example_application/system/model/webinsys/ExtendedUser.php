<?php
PhpBURN::import('webinsys.Users');
class Users2 extends Users {
	public $_package = 'webinsys';
	public $_tablename = 'users2';
	
	public function _mapping() {
		$this->getMap()->addField('id_user2','id_user2','int',10,array('autoincrement' => true, 'primary' => true));
		$this->getMap()->addField('id_user','id_user','int',10,array());
		$this->getMap()->addField('name_user','name_user','varchar', null, array() );
		
		
		$this->getMap()->addParentField('id_user');
	}
}

class ExtendedUser extends Users2 {
	public $_package = 'webinsys';
	public $_tablename = 'extended_user';
	
	public function _mapping() {
		$this->getMap()->addField('id_extended_user','id_extended_user','int',10,array('autoincrement' => true, 'primary' => true));
		$this->getMap()->addField('id_user2','id_user2','int',10,array());
		$this->getMap()->addField('last_name','last_name','varchar', null, array() );
		
		$this->getMap()->addParentField('id_user2');
	}
}


?>