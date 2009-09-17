<?php
class main extends Controller {
	public function __construct() {
		//Call your globals here
	}
	
	public function index() {
		//Loading model
		PhpBURN::import('webinsys.ExtendedUser');

		$user = new Users();

		$user->order('id_user','DESC');
		//$user->swhere('name','=','Lisa Simpson');
		$user->getMap()->fields['id_user']['parentReferences'] = get_class($user);
		$user->swhere('name','=','Megie Simpson');
		$user->find();
		while($user->fetch()) {
			$this->_viewData['name'] = $user->name;
			$user->name = 'Name Test';
			//$user->last_name = 'Last Name Test';
			//$user->name_user = 'Name User Test';
			//$user->__PhpBURN_Extended_Users->name = 'Test';
			$user->save();
		}

		
		
		
		//print $user->getMap()->fields['name']['#value'];
	}
	
	public function teste() {
		//Loading model
		PhpBURN::import('webinsys.Users');

		$user = new Users();

		$user->order('id_user','DESC');
		$user->find();
		while($user->fetch()) {
			$this->_viewData['name'] = $user->name;
		}
		
		$this->loadView('main/index',$this->_viewData);
	}
}
?>