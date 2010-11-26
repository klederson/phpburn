<?php
class main extends Controller {
	public function __construct() {
            parent::__construct();
            //Call your globals here
	}
	
	public function index() {
            $this->loadView('wellcome', array());
	}
}
?>