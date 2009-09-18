<?php
class main extends Controller {
	public function __construct() {
		//Call your globals here
	}
	
	public function index() {
		
	}
	
	public function select() {
		//Loading model
		PhpBURN::import('webinsys.ExtendedUser');

		$user = new ExtendedUser();

		$user->order('id_user','DESC');
		$user->find();
		print "<pre>";
		while($user->fetch()) {
			print_r($user->toArray());
		}
		print "</pre>";
	}
	
	public function selectWithWhere() {
		//Loading model
		PhpBURN::import('webinsys.ExtendedUser');

		$user = new ExtendedUser();

		$user->order('id_user','DESC');
		$user->swhere('name','=','Klederson');
		$user->find();
		print "<pre>";
		while($user->fetch()) {
			print_r($user->toArray());
		}
		print "</pre>";
	}
	
	public function selectAndSave() {
		//Loading model
		PhpBURN::import('webinsys.ExtendedUser');

		$user = new ExtendedUser();

		$user->order('id_user','DESC');
		$user->find();
		print "<pre>";
		while($user->fetch()) {
			$user->name = 'Test Name';
			$user->last_name = 'Test Last Name';
			$user->name_user = 'Test Name User';
			$user->save();
			print_r($user->toArray());
		}
		print "</pre>";
	}
	
	public function insert() {
		PhpBURN::import('webinsys.ExtendedUser');

		$user = new ExtendedUser();
		
		$user->name = 'Klederson';
		$user->last_name = 'Bueno';
		$user->name_user = 'Acid';
		
		$user->save();
		
		print "<pre>";
		print_r($user->toArray());
		print "</pre>";
	}
}
?>