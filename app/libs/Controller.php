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
		
		if($rand == true) {
			shuffle($files);
		}
		
		foreach($files as $index => $value) {
			$files[$index] = str_replace($folder,"",$files[$index]);
		}
		
		$returnArray = $amount == null || !is_numeric($amount) ? $files : array_slice($files,0,$amount);
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
	public function callErrorPage($page = '404') {
		PhpBURN_Message::output('[!Calling error page:!] '.$page,PhpBURN_Message::ERROR);
		require_once(SYS_VIEW_PATH . DS . '_errorPages' . DS . $page . '.php');
		exit;
	}

        /**
         * Calls a controller Action
         * @param String $action
         * @param Array $parms
         *
         * @return Mixed
         */
	public function callAction($action, $parms) {
		//Calling action
		call_user_func_array(array($this,$action),$parms);
		if(PhpBURN_Views::$autoLoad == true) {
			$this->loadRelativeView($action,false,true);
		}
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
		if(array_search('PhpBURN_Views',get_declared_classes()) == true) {
			return PhpBURN_Views::loadView(get_class($this) . DS .$action, $this->_viewData, $toVar);
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
		$parms = array_slice($parms,2);
		
		$filename = sprintf("%s.%s", SYS_CONTROLLER_PATH . DS . $controllerName, SYS_CONTROLLER_EXT);
		require_once($filename);
		
		return call_user_func_array(array($controllerName,$method),$parms);
	}
}
?>