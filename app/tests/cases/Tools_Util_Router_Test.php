<?php

phpBurn::load('Tools.Util.Router');
phpBurn::load('Controller');

/** My Fake controller just for tests */
class tools extends Controller {

	public $calledAction;
	public $actionParams;
	public $errorNum;

	// dummy function so we dont need to include files
	public function controllerExists() {
		return true;
	}

	public function callAction($action, $params) {
		$this->calledAction = $action;
		$this->actionParams = $params;
	}

	public function util() {
	}
}

class Tools_Util_Router_Test extends PHPUnit_Framework_TestCase {

	public $scriptName;
	public $requestURI;

	public function setUp() {
		parent::setUp();

		$this->scriptName = @$_SERVER['SCRIPT_NAME'];
		$this->requestURI = @$_SERVER['REQUEST_URI'];

		$_SERVER['SCRIPT_NAME'] = 'index.php';
		$_SERVER['REQUEST_URI'] = '/tools/util/router';
	}

	public function testDefaultOptionsForRoutes() {

		$Router = new Router(Array()); 

		$this->assertArrayHasKey('__defaultAction', Router::$routes);
		$this->assertArrayHasKey('__defaultController', Router::$routes);
		$this->assertEquals(Router::$routes['__defaultAction'], 'index');
		$this->assertEquals(Router::$routes['__defaultController'], 'main');
	}

	public function testRouteMatch() {
		$Router = new Router(Array(
			'about' => 'pages/about',
			'help' => 'pages/help'
		));
		// using $this->uri
		$this->assertTrue($Router->routeMatch('tools/util/router'));
		$this->assertFalse($Router->routeMatch('/tools/util/router'));
		$this->assertFalse($Router->routeMatch('tools/util/router/'));
		$this->assertFalse($Router->routeMatch('/tools/util/router/'));
		$this->assertFalse($Router->routeMatch('tools/util'));

		// specifc $uri
		$this->assertTrue($Router->routeMatch('tools/([0-9]+)', 'tools/123'));
		$this->assertFalse($Router->routeMatch('tools/[0-9]+', 'tools/util'));
	}

	public function testParseRoute() {
		$Router = new Router(Array(
			'about' => 'pages/about',
			'help' => 'pages/help'
		));
		$route = $Router->parseRoute();
		$this->assertTrue(is_array($route));
		$this->assertArrayHasKey('index', $route);
		$this->assertArrayHasKey('action', $route);
		$this->assertEquals($route['index'], 'tools');
		$this->assertEquals($route['action'], 'tools/util/router');
	}

	public function testExecuteRoute() {
		$Router = new Router(Array());
		$route = $Router->parseRoute();
		$Router->executeRoute($route);
		$this->assertEquals($Router->controller->calledAction, 'util');
		$this->assertEquals($Router->controller->actionParams, array('router'));
	}

    /**
	 * Cannot test error 404... we need to change things
     */
	public function testExecuteRouteError404() {
		//$_SERVER['REQUEST_URI'] = '/tools/nonexisting/action';
		//$Router = new Router(Array());
		//$route = $Router->parseRoute();

		//ob_start();
	//	$Router->executeRoute($route);
		//$content = ob_get_contents();
		//ob_end_clean();

		//$this->assertRegExp('/404/', $content);
	}

	public function tearDown() {
		parent::tearDown();

		$_SERVER['SCRIPT_NAME'] = $this->scriptName;
		$_SERVER['REQUEST_URI'] = $this->requestURI;

		unset($this->scriptName, $this->requestURI);
	}

}

?>
