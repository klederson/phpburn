<?php
/**
 * DialectManager Class
 * 
 * This class manage the dialects for each package.
 * Can be used by many ways but by default its better if you simply do not mess around ;) let the application work for you.
 * 
 * @package PhpBURN Dialects
 * 
 * @author Kléderson Bueno <klederson@klederson.com>
 * @version 0.1a
 *
 */
class PhpBURN_DialectManager
{
	/**
	 * This variable storage in runtime all dialects for each kind of package. For more details 
	 * @see getDialect()
	 * @var Array
	 */
	private static $dialects = array();
	
	/**
	 * Creates a new Dialect or retreive the existing one based on the Model and Configuration.
	 * 
	 * @param PhpBURN_ConfigurationItem $config
	 * @param PhpBURN_Core $obj
	 * @return PhpBURN_Dialect
	 */
	public function create(PhpBURN_ConfigurationItem $config,PhpBURN_Core $obj) {
		$dialect = self::getDialect($config->dialect);
		if(!$dialect) {			
			//Loads the interface for dialect uses
			PhpBURN::load('Dialect.IDialect');
			
//			Loads the correspondent dialect based on config
			if(PhpBURN::load("Dialect.$config->dialect") != "error") {
				$className = self::getDialectClass($config->dialect);
				
//				Instance the dialect
				$dialectClass = new $className($obj);
				
				$dialect = self::$dialects[$config->package] = $dialectClass;
				unset($dialectClass);
			} else {
				PhpBURN_Message::output('[!Cannot find dialect!]: ' . $config->dialect, PhpBURN_Message::EXCEPTION);
			}
		}
		
		return $dialect;
	}
	
	/**
	 * Retreive the current dialect for each package
	 * 
	 * @param String $package
	 * @return PhpBURN_Dialect
	 * @return Booelan
	 */
	public function getDialect($package = null) {
		if(!isset(self::$dialects[$package])) {
			return false;
		} else {
			return self::$dialects[$package];
		}
	}
	
	/**
	 * Discover the correspondent class for the Dialect
	 * 
	 * @param String $dialect
	 * @return unknown_type
	 */
	private function getDialectClass($dialect = null) {
		$dialect = $dialect = null ? "MySQL" : $dialect;
		
		return "PhpBURN_Dialect_$dialect";
	}
	
}
?>