<?php
PhpBURN::load('Tools.Util.Router');
PhpBURN::load('Tools.Controller.IController');
include_once(SYS_APPLICATION_PATH . DS . 'config' . DS . 'routes.php');

//Define the main route functions
$router = new Router($routes);
$currentRoute = $router->parseRoute();
if($currentRoute != false) {
	$router->executeRoute($currentRoute);
} else {
	Controller::callErrorPage('404');
}

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
		
		$this->callView($action);
	}
	
	public function callView($action) {
		
		
		//Searching if Views is loaded
		if(array_search('PhpBURN_Views',get_declared_classes()) == true) {
			//Getting the path view
			$viewPath = SYS_VIEW_PATH . get_class($this) . DS .$action . '.' . SYS_VIEW_EXT;
			
			PhpBURN_Message::output('[!Loading view:!] ' . $viewPath);
			
			
		}
	}
}
?>