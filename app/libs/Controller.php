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
  
  public static $stack = array(
      'url',
      'controller',
      'action',
      'params' => array()
  );

  public function __construct() {

  }

  /**
   * List all files into a specified directory.
   *
   * @param String $folder
   * @param String $extension
   * @param Integer $amount
   * @param Boolean $rand
   *
   * @return Array
   */
  public function getFilesFromFolder($folder, $extension = "*", $amount = null, $rand = true) {
    $files = glob($folder . DS . $extension);

    if ($rand == true) {
      shuffle($files);
    }

    foreach ($files as $index => $value) {
      $files[$index] = str_replace($folder, "", $files[$index]);
    }

    $returnArray = $amount == null || !is_numeric($amount) ? $files : array_slice($files, 0, $amount);
    unset($files);

//		print_r($returnArray);

    return $returnArray;
  }

  /**
   * Call error page located at SYS_VIEW_PATH/_errorPages just like a view
   * and then exit the application.
   *
   * @param String $page
   */
  public function callErrorPage($page = '404', $content = array()) {
    PhpBURN_Message::output('[!Calling error page:!] ' . $page, PhpBURN_Message::ERROR);
    
    if( class_exists('PhpBURN_Views') ) {
      self::loadView('_errorPages/'.$page,$content);
    } else {
      require_once(SYS_VIEW_PATH . DS . '_errorPages' . DS . $page . '.php');
    }
    
    exit;
  }

  /**
   * Executes functions settedUp on STATIC $onCallActionBefore
   * @param String $action
   * @param Array $parms
   */
  public function callActionBefore($controllerName, $action, array $parms) {
    if (array_search('PhpBURN_ControllerConfig', get_declared_classes()) == true) {
      if (is_array(PhpBURN_ControllerConfig::getOnCallActionBefore()) && count(PhpBURN_ControllerConfig::getOnCallActionBefore()) > 0) {
        foreach (PhpBURN_ControllerConfig::getOnCallActionBefore() as $function) {
          $function($controllerName, $action, $parms);
        }
      }
    } else {
      PhpBURN_Message::output('[!onCallActionBefore cannot be loaded because PhpBURN_ControllerConfig is not instanced - Please add PhpBURN::load("Tools.Controller.ControllerConfig"); to your config/controller.php Configurations!]', PhpBURN_Message::WARNING);
    }
  }

  /**
   * Executes functions settedUp on STATIC $onCallActionAfter
   * @param String $action
   * @param Array $parms
   */
  public function callActionAfter($controllerName, $action, array $parms) {
    if (array_search('PhpBURN_ControllerConfig', get_declared_classes()) == true) {
      if (is_array(PhpBURN_ControllerConfig::getOnCallActionAfter()) && count(PhpBURN_ControllerConfig::getOnCallActionAfter()) > 0) {
        foreach (PhpBURN_ControllerConfig::getOnCallActionAfter() as $function) {
          $function($controllerName, $action, $parms);
        }
      }
    } else {
      PhpBURN_Message::output('[!onCallActionBefore cannot be loaded because PhpBURN_ControllerConfig is not instanced - Please add PhpBURN::load("Tools.Controller.ControllerConfig"); to your config/controller.php Configurations!]', PhpBURN_Message::WARNING);
    }
  }

  /**
   * Calls a controller Action
   * @param String $action
   * @param Array $parms
   *
   * @return Mixed
   */
  public function callAction($action, $parms) {

    //onCallActionBefore
    $this->callActionBefore(get_class($this), $action, $parms);

    //Calling action
    call_user_func_array(array($this, $action), $parms);
    if (PhpBURN_Views::$autoLoad == true) {
      $this->loadRelativeView($action, false, true);
    }

    //onCallActionAfter
    $this->callActionAfter(get_class($this), $action, $parms);
  }

  /**
   * Same as loadView but loads relative to controllers name
   *
   * @param String $action
   * @param Boolean $toVar
   *
   * @return String
   */
  public function loadRelativeView($action, $toVar = false) {
    //Searching if Views is loaded
    if (array_search('PhpBURN_Views', get_declared_classes()) == true) {
      return PhpBURN_Views::loadView(get_class($this) . DS . $action, $this->_viewData, $toVar);
    }
  }

  /**
   * Loads a view, process data and print/store it.
   *
   * @param String $view
   * @param Array $data
   * @param Boolean $toVar
   *
   * @return String
   */
  public function loadView($view, array $data, $toVar = false) {
    return PhpBURN_Views::loadView($view, $data, $toVar);
  }

  /**
   * Call a controller method
   *
   * @param String $controllerName
   * @param String $method
   * @param Mixed [param1, param2, param3, ...]
   *
   * @return Mixed
   */
  public function callControllerMethod($controllerName, $method) {
    $parms = func_get_args();
    $parms = array_slice($parms, 2);

    $filename = sprintf("%s.%s", SYS_CONTROLLER_PATH . DS . $controllerName, SYS_CONTROLLER_EXT);
    require_once($filename);

    return call_user_func_array(array($controllerName, $method), $parms);
  }

}

?>