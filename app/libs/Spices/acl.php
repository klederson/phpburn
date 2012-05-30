<?php

namespace PhpBURN\Spices\ACL;

use PhpBURN\Spices\PDocs\PDocs as docs;

class PhpBURN_ACL {

  private static $aclSettings;
  public static $callBacks;

  public function checkPermissions($controllerName, $action, $parms) {
    $methodRules = PhpBURN_ACL_Control::$classRules->$controllerName->$action;

    if (self::isAllowed($controllerName,$action,$parms)) {
      $function = &self::$callBacks['granted'];
      $function($controllerName, $action, $parms);
    } else {
      $function = &self::$callBacks['denied'];
      $function($controllerName, $action, $parms);
    }
  }
  
  private static function isAllowed($controllerName,$action, array $parms = array()) {
    $methodRules = PhpBURN_ACL_Control::$classRules->$controllerName->$action;

    if (self::$aclSettings['authInfo']['allowedMethods'][$controllerName][$action] == true) {
      return TRUE;
    } else if (!isset(self::$aclSettings['authInfo']['allowedMethods'][$controllerName][$action]) && $methodRules->aclDefault == 'allow') {
      return TRUE;
    } else if (!isset(self::$aclSettings['authInfo']['allowedMethods'][$controllerName][$action]) && $methodRules->aclDefault != 'deny' && self::$aclSettings['defaultPermission'] == 'allow') {
      return TRUE;
    } else {
      return FALSE;
    }
  }

  public static function isAllowedUrl($url) {
    include(SYS_APPLICATION_PATH . DS . 'config' . DS . 'routes.php');

    $route = new \Router($routes, '/index.php', $url);

    $structure = $route->getCallStructure();
        
    return self::isAllowed($structure['controller'], $structure['action'], $structure['parms']);
  }

  public function setCallBack(array $callBack) {
    self::$callBacks = $callBack;
  }

  public static function setConfig(array $config, $name = null) {
    if ($name == null) {
      self::$aclSettings = $config;
    } else {
      self::$aclSettings[$name] = $config;
    }
  }

}

class PhpBURN_ACL_Control {

  public static $aclProperties = array(
      'aclDefault',
      'aclAlias',
      'aclVisible',
      'aclDesc',
      'aclType',
      'aclIgnore'
  );
  public static $classRules;

  /**
   * It populates self::$classRules with all Controllers Methods Rules
   * 
   * <p>
   * It populates self::$classRules with all Controllers Methods Rules based on
   * self::$aclProperties
   * </p>
   *
   * @see PhpBURN ACL Documentation
   */
  public static function generateRules() {
    foreach (self::getControllerList() as $className => $arrContent) {
      $methods = self::getControllerMethods($className);

      foreach ($methods as $method) {
        foreach (self::$aclProperties as $aclProp) {
          self::$classRules->$className->$method->$aclProp = self::getMethodRule($className, $method, $aclProp);
        }
      }
    }
  }

  public static function getMethodRule($controllerName, $methodName, $tag) {
    $reflectionMethod = new \ReflectionMethod($controllerName, $methodName);
    $methodComment = $reflectionMethod->getDocComment();

    return self::getCommentTag($methodComment, $tag);
  }

  /**
   * Search for all methods from a controller ( and automaticaly excludes Controller methods )
   * @param String $controllerName
   * @return Array
   */
  public static function getControllerMethods($controllerName) {
    $ignoredMethods = get_class_methods('Controller');
    $controllerMethods = get_class_methods($controllerName);

    return array_diff($controllerMethods, $ignoredMethods);
  }

  /**
   * Get all aplication Controllers list with className and Path
   * @return Array
   */
  public static function getControllerList() {
    $controllerList = array();

    foreach (glob(SYS_CONTROLLER_PATH . DS . '*.' . SYS_CONTROLLER_EXT) as $fileName) {
      $nameArray = explode(DS, $fileName);
      $className = str_replace('.' . SYS_CONTROLLER_EXT, '', $nameArray[count($nameArray) - 1]);

      require_once $fileName;
      $controllerList[$className]['className'] = $className;
      $controllerList[$className]['path'] = $fileName;
      $controllerList[$className]['name'] = $className;
    }

    return $controllerList;
  }

  /**
   * Get a comment tag from a commentString ( phpdoc format )
   * @param String $comment
   * @param String $tag
   * @return String
   */
  public static function getCommentTag($comment, $tag = '') {
    if (empty($tag)) {
      return $str;
    }

    $matches = array();
    preg_match("/" . $tag . " (.*)(\\r\\n|\\r|\\n)/U", $comment, $matches);

    if (isset($matches[1])) {
      return trim($matches[1]);
    }

    return '';
  }

  public static function listControllerRules($controllerName) {
    $methods = self::getControllerMethods($controllerName);

    foreach ($methods as $method) {
      
    }
  }

}

?>
