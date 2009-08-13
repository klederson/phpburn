<?php
class main extends Controller {
	public function __construct() {
		//Call your globals here
	}
	
	public function index() {
		//Loading model
		PhpBURN::import('webinsys.Users');

		$user = new Users();

		$user->order('id_user','DESC');
		$user->find();
		while($user->fetch()) {
			$this->_viewData['name'] = $user->name;
		}
				
	}
}
?>