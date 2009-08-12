<?php
/**
 * This class controls the main functions of controllers and actions calls
 * 
 * @version 0.1
 * @package PhpBURN
 * @subpackage Controllers
 * 
 * @author Klederson Bueno <klederson@klederson.com>
 */

PhpBURN::load('Tools.Util.Router');
include_once(SYS_APPLICATION_PATH . DS . 'config' . DS . 'routes.php');

$router = new Router($routes);
$currentRoute = $router->parseRoute();
if($currentRoute != false) {
	$router->executeRoute($currentRoute);
} else {
	Controller::callErrorPage('404');
}

require_once('IController.php');

abstract class Controller {	
	protected function begin() {
		
	}
	
	public function callErrorPage($page = '404') {
		PhpBURN_Message::output('[!Calling error page:!] '.$page,PhpBURN_Message::ERROR);
		require_once(SYS_VIEW_PATH . DS . '_errorPages' . DS . $page . '.php');
	}
}
?>