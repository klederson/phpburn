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
  const MATCH_ALL = '([a-zA-Z0-9 _&%+.-]+)';

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
    $this->baseUrl = explode('/', $_SERVER['SCRIPT_NAME']);
    $this->queryUrl = explode('/', $_SERVER['REQUEST_URI']);

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

    if (count($this->urlDiff) > 0) {
      foreach ($this->urlDiff as $index => $value) {
        if ($value != '' && !empty($value) && $value != ' ') {
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
    $uri = $uri == null ? implode('/', $this->uri) : $uri;
    return preg_match('#^' . $route . '$#', $uri);
  }

  public function parseRoute($routes = null) {
    $routes = $routes == null ? self::$routes : $routes;
    self::parseDiff();

    //Searching for pre-defined routes
    foreach ($routes as $index => $value) {
      if (self::routeMatch($index)) {
        $return['index'] = $index;

        $parms = explode('/', $index);
        $parms = array_slice($parms, 1, count($parms) - 1);

        $finalValue = null;
        if (count($parms) >= 1) {
          foreach ($parms as $parmIndex => $parmValue) {
            $argRegex = sprintf('(\$%s)',$parmIndex+1);
            $value = preg_replace($argRegex, $this->uri[$parmIndex + 1], $value);
          }
          $finalValue = $value;
        }

        $return['action'] = $finalValue == null ? $value : $finalValue;

        return $return;
      }
    }


    //Searching for file
    if (!empty($this->uri[0]))
      if (file_exists(SYS_CONTROLLER_PATH . $this->uri[0] . '.' . SYS_CONTROLLER_EXT) === true) {
        $return['index'] = $this->uri[0];
        $return['action'] = count($this->uri) > 1 ? implode('/', $this->uri) : $this->uri[0] . '/' . self::$routes['__defaultAction'];

        return $return;
      }

    //Tracking default controller (only if nothing is passed in the URL, eg.: www.mysite.com insted www.mysite.com/something)
    if (count($this->uri) == 0) {
      $return['index'] = self::$routes['__defaultController'];
      $return['action'] = self::$routes['__defaultController'];

      return $return;
    }

    //No matches... the controller does not exist
    return false;
  }

  public function prepareRoute($route) {
    
  }

  public function executeRoute(array $route) {

    $route = explode('/', $route['action']);

    include_once(SYS_CONTROLLER_PATH . $route[0] . '.' . SYS_CONTROLLER_EXT);

    $parms = array_slice($route, 2, count($route) - 2);

    $controller = new $route[0];

    if (count($route) > 1) {
      $action = count($route) == 1 ? $route[0] : $route[1];
    } else {
      $action = self::$routes['__defaultAction'];
    }

    if (method_exists($controller, $action)) {
      $controller->callAction($action, $parms);
    } else {
      Controller::callErrorPage('404');
    }
  }

}

?>