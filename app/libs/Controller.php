<?php
PhpBURN::load('Tools.Controller.IController');

/**
 * This class controls the main functions of controllers and actions calls
 * 
 * @version 0.1
 * @package PhpBURN
 * @subpackage Controllers
 * 
 * @author Klederson Bueno <klederson@klederson.com>
 */
abstract class Controller {	
	
	public $_viewData = array();
	
	protected function begin() {
		
	}
	
	public function callErrorPage($page = '404') {
		PhpBURN_Message::output('[!Calling error page:!] '.$page,PhpBURN_Message::ERROR);
		require_once(SYS_VIEW_PATH . DS . '_errorPages' . DS . $page . '.php');
		exit;
	}
	
	public function callAction($action, $parms) {
		//Calling action
		call_user_func_array(array($this,$action),$parms);
		if(PhpBURN_Views::$autoLoad == true) {
			$this->loadRelativeView($action,false,true);
		}
	}
	
	public function loadRelativeView($action, $toVar = false) {
		//Searching if Views is loaded
		if(array_search('PhpBURN_Views',get_declared_classes()) == true) {
			return PhpBURN_Views::loadView(get_class($this) . DS .$action, $this->_viewData, $toVar);
		}
	}
	
	public function loadView($view, $data, $toVar = false) {
		return PhpBURN_Views::loadView($view, $data, $toVar);
	}
}
?>