<?php
class main extends Controller {
	public function __construct() {
		//Call your globals here
	}
	
	public function index() {
		print 'this is my main controller';
		
		//Loading model
		PhpBURN::import('webinsys.Users');

		$user = new Users();

		$user->order('id_user','DESC');
		$user->find();
		
		while($user->fetch()) {
			print "<br/>";
			print $user->name;
		}
		
		$this->_viewData = "Oi";		
		
	}
}
?>