<?php
/**
 * This is the main file and the only what needs to be included
 * in your code.
 * @package PhpBURN Beta 1
 * @version 0.1
 */

//Defines absolute directory of PHPBURN
define('PHPBURN_INCLUDE_PATH', dirname(__FILE__), true);

/**
 * Main class for PHPBURN
 * 
 * @method import
 * @method load
 */

abstract class PhpBURN {
	/**
	 * This method loads internals classes as Configuration, Exceptions, Connection, etc.
	 * 
	 * @author Kléderson Bueno <klederson@klederson.com>
	 * @access public
	 */
	public static function load() {
		$args = func_get_args();
		foreach($args as $libname)
		{
		
			$basedir = PHPBURN_INCLUDE_PATH . DIRECTORY_SEPARATOR . 'libs' . DIRECTORY_SEPARATOR;
			$libname = preg_replace('@^PhpBURN_@', '', $libname);
			$newfile = $basedir . str_replace('.', DIRECTORY_SEPARATOR, $libname). '.php';
		
			if(file_exists($newfile)) {
				require_once $newfile;
			} else {
				return "error";
			}
			
			
		}
	}
	
	public function loadModule() {
		$args = func_get_args();
		foreach($args as $module) {
			PhpBURN::loadConfig($module);
			PhpBURN::load($module);
		}
	}
	
	public function loadConfig($moduleName) {
		$configFile = strtolower($moduleName) . '.php';
		
		require_once(SYS_APPLICATION_PATH . DS . 'config' . DS . $configFile);
	}
	
	/**
	 * This method loads model(s) from packages
	 * 
	 * @example 
	 * <code>
	 * PhpBURN::import('package.Model');
	 * </code>
	 * @example 
	 * <code>
	 * PhpBURN::import('package.subpackage.SubModel');
	 * </code>
	 * 
	 * @access public
	 */
	public static function import() {
		$args = func_get_args();
		
		foreach($args as $libname)
		{
			$lines = explode('.',$libname);
			$config = PhpBURN_Configuration::getConfig($lines[0]);
			
			$basedir = $config->class_path;
			$libname = preg_replace('@^PhpBURN_@', '', $libname);
			$newfile = $basedir . str_replace('.', DIRECTORY_SEPARATOR, $libname). '.php';
		
			if(file_exists($newfile)) {
				PhpBURN_Message::output('[!Loading Model!]: '. $newfile, null, PhpBURN_Message::LOW);
				require_once $newfile;
			} else {
				return "error";
			}			
		}
	}
	
	/**
	 * Loads a xml file directly from package or subpackage
	 * Without concern about WHERE it is
	 * 
	 * PhpBURN::loadXMLMapping('phpburn.super.model.Users');
	 * Will look for phpburn directory / super / model / mapping / Users.xml
	 *
	 * @return String
	 * @return Boolean
	 */
	public static function loadXMLMapping() {
		$args = func_get_args();
		foreach($args as $configname)
		{
			$lines = explode('.',$configname);
			$config = PhpBURN_Configuration::getConfig($lines[0]);
			$basedir = $config->class_path;
			
			$length = count($lines);
			
			$newpath = "";
			foreach($lines as $key => $value) {
				if($key == ($length-1)) {
					$newpath .= "_mapping".DIRECTORY_SEPARATOR.$value.".xml";
				} else {
					$newpath .= $value . DIRECTORY_SEPARATOR;
				}
			}
			
			$filepath = $basedir . $newpath;
			if(file_exists($filepath)) {
				return PhpBURN::loadFile($filepath);
			} else {
				return "error";
			}
		}
	}
	
	/**
	 * Loads any file
	 *
	 * @param FilePath
	 * @return String
	 */
	public static function loadFile($filename) {
		$file = file($filename);
		$content = '';
		if(file_exists($filename)) {
			foreach ($file as $key => $value) {
			   $content .= $value;
			}
		} else {
			$content = "error";
		}
		return $content;
	}
	
	public function startApplication() {
		if(array_search('Controller',get_declared_classes()) == true) {
			PhpBURN::load('Tools.Util.Router');
			include_once(SYS_APPLICATION_PATH . DS . 'config' . DS . 'routes.php');
			
			//Define the main route functions
			$router = new Router($routes);
			$currentRoute = $router->parseRoute();
			if($currentRoute != false) {
				$router->executeRoute($currentRoute);
			} else {
				Controller::callErrorPage('404');
			}
		}
	}
	
	public function redirect($direction) {
		echo "<script> document.location='".SYS_BASE_URL."$direction'; </script>";
	}
	
	public function go($index) {
		echo "<script> document.history($index) </script>";
	}
}
PhpBURN::load('Configuration','Message');
?>
