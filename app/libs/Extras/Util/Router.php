<?php
/**
 * Router Class
 * This class manages the controller routes and all operations based in routing such as URI treatment and matches.
 * 
 * @package PhpBURN
 * @subpackage Router
 * 
 * @version 0.1
 * @author Klederson Bueno <klederson@klederson.com>
 *
 */
class Router {
	
	public $uri = array();
	public $baseUrl;
	public $queryUrl;
	public $urlDiff;
	
	static $routes;
	
	
	/**
	 * This constant is just a help to users find their better REGEX expression for eatch part into the url
	 * In this case it will match ALL url chars.
	 * <example>
	 * http://yourwebsite.com/my_action/
	 * http://yourwebsite.com/my action/
	 * http://yourwebsite.com/my+action/
	 * http://yourwebsite.com/my_action1/
	 * </example>
	 * @var String
	 */
	const MATCH_ALL = '([a-zA-Z0-9 _&%+]+)';
	
	/**
	 * This constant is just a help to users find their better REGEX expression for eatch part into the url
	 * In this case it will match ALL NUMERIC url chars.
	 * <example>
	 * http://yourwebsite.com/1/
	 * http://yourwebsite.com/222/
	 * </example>
	 * @var String
	 */
	const MATCH_NUMERIC = '([0-9]+)';
	
	/**
	 * This constant is just a help to users find their better REGEX expression for eatch part into the url
	 * In this case it will match ALL NUMERIC url chars.
	 * <example>
	 * http://yourwebsite.com/1/
	 * http://yourwebsite.com/22 2/
	 * </example>
	 * @var String
	 */
	const MATCH_NUMERIC_URLRAW = '([0-9 %]+)';
	const MATCH_NUMERIC_URLENCODE = '([0-9 +]+)';
	
	const MATCH_ALPHANUMERIC = '([a-zA-Z0-9]+)';
	const MATCH_ALPHANUMERIC_URLRAW = '([a-zA-Z0-9 %]+)';
	const MATCH_ALPHANUMERIC_URLENCODE = '([a-zA-Z0-9 +]+)';
	
	const MATCH_STRING = '([a-zA-Z]+)';
	const MATCH_STRING_URLRAW = '([a-zA-Z %]+)';
	const MATCH_STRING_URLENCODE = '([a-zA-Z +]+)';
	
	public function __construct($routes) {
		$this->baseUrl = explode('/',$_SERVER['SCRIPT_NAME']);
		$this->queryUrl = explode('/',$_SERVER['REQUEST_URI']);
		
		$this->urlDiff = array_diff_assoc($this->queryUrl, $this->baseUrl);
		
		self::$routes = &$routes;
	}
	
	public function __destruct() {
		unset($this->baseUrl, $this->queryUrl, $this->urlDiff, $this->uri);
	}
	
	/**
	 * Router->parseDiff() construct the URI reference discarting the equivalent to SYS_BASE_URL
	 * 
	 * @return Array
	 */
	public function parseDiff() {	
		unset($this->uri);
		$this->uri = array();
		
		if(count($this->urlDiff) > 0) {
			foreach($this->urlDiff as $index => $value) {
				if($value != '' && !empty($value) && $value != ' ') {
					array_push($this->uri, $value);
				}
			}
		}
		
		return $this->uri;
	}
	
	/**
	 * Matches a route string with the given uri, if no uri is given it uses $this->uri
	 * 
	 * @param String $route
	 * @param String $uri
	 * @return Booelan
	 */
	public function routeMatch($route, $uri = null) {
		$uri = $uri == null ? implode('/',$this->uri) : $uri;
		return preg_match('#^'.$route.'$#', $uri);
	}

	public function parseRoute($routes = null) {
		$routes = $routes == null ? self::$routes : $routes;
		
		self::parseDiff();
		
		foreach($routes as $index => $value) {
			if(self::routeMatch($index)) {
				$return['index'] = $index;
				$return['action'] = $value;
				
				return $return;
			}
		}
		
		return false;
	}
}
?>