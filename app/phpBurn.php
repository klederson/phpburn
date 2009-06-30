<?php
/**
 * This is the main file and the only what needs to be included
 * in your code.
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
	
	/**
	 * This method loads model(s) from packages
	 * @example PhpBURN::import('package.Model');
	 * @example PhpBURN::import('package.subpackage.SubModel');
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
	 * @return unknown
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

}
PhpBURN::load('Configuration','ConnectionManager', 'IPhpBurn', 'Core', 'DialectManager', 'Mapping','Message');
?>
