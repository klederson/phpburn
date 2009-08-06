<?php
require_once('app/phpBurn.php');
require_once('config.php');
print "<pre>";
//Turn the Messages and Logs and Erros ON
PhpBURN_Message::setMode(PhpBURN_Message::FIREBUG); //You can Choose FIREPHP, BROWSER OR FILE for now than more can came latter

//Loading the configuration file
$config = new PhpBURN_Configuration($thisConfig);

PhpBURN::load('Extras.Util.Router');

$route['album'] = "albumsController";
$route['album/([a-zA-Z]+)'] = "albumsController/$1";
$route['album/([a-zA-Z]+)/([a-zA-Z0-9 ]+)'] = "albumsController/$1/$2";

$router = new Router($route);
$rota = $router->parseRoute();

var_dump($rota);

$album = new Album();
$album->test();
print "<pre>";

abstract class Controller {
	
	public $defaultController = 'home';
	public $defaultAction = 'index';
	
	public function begin() {
		
	}
	
	public function getActions($class) {
		$controllerMethods = get_class_methods(Controller);
		$allMethods = get_class_methods($class);
		
		$remainMethods = array_diff_assoc($allMethods, $controllerMethods);
		
		var_dump($remainMethods,$allMethods, $controllerMethods);
		
	}
	
}



class Album extends Controller {
	public function test() {
		$this->getActions($this);
	}
	
	public function oi() {
		
	}
}
?>