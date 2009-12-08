<?php
class Users extends PhpBURN_Core {
	public $_package = 'webinsys';
	public $_tablename = 'users';

	public $id_user;
	public $first_name;
	public $last_name;
	public $password;
	public $created_at;
	
	public function _mapping() {
		$this->getMap()->addField('id_user','id_user','int',10,array('autoincrement' => true, 'primary' => true));
		$this->getMap()->addField('email','email','varchar',255, array() );
		$this->getMap()->addField('first_name','first_name','varchar',255, array() );
		$this->getMap()->addField('last_name','last_name','varchar',255, array() );
		$this->getMap()->addField('password','password','varchar',255, array() );
		$this->getMap()->addField('gender','gender','enum', 'male,female', array() );
		$this->getMap()->addField('birthday','birthday','timestamp',null, array() );
		$this->getMap()->addField('created_at','created_at','timestamp', null, array('default_value' => 'CURRENT_TIMESTAMP') );
	}
	
	public function doLogin() {
		if( $this->find() == 1) {
			$this->fetch();
			$this->registerLoginSession();
			return true;
		}  else {
			return false;
		}
	}
	
	private function registerLoginSession() {
		@session_start();
		
		$_SESSION['webinsys']['user']['id_user'] = $this->id_user;
		$_SESSION['webinsys']['user']['email'] = $this->email;
	}
	
	public function checkLogin() {
		if (isset($_SESSION['webinsys']['user']['id_user'])) {
			$this->id_user = $_SESSION['webinsys']['user']['id_user'];
			$this->email = $_SESSION['webinsys']['user']['email'];
			if ($this->find() == 1)
				return true;
		}
		
		@session_destroy();
		return false;
	}
}
?>