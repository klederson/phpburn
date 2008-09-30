<?php
class PhpBURN_Dialect
{
	private static $dialects = array();
	
	public function create(PhpBURN_ConfigurationItem $config,PhpBURN_Core $obj) {
		$dialect = $this->getDialect($config->dialect);
		if(!$dialect) {
			//Create a new dialect
			
			//Loads the interface for dialect uses
			PhpBURN::load('Dialect.IDialect');
			
			if(PhpBURN::load("Dialect.$config->dialect") != "error") {
				$className = $this->getDialectClass($config->dialect);
				$dialectClass = new $className($obj);
				
				$dialect = self::$dialects[$config->package] = $dialectClass;
			} else {
				exit();
			}
		}
		
		return $dialect;
	}
	
	public function getDialect($package = null) {
		if(!isset(self::$dialects[$package])) {
			return false;
		} else {
			return self::$dialects[$package];
		}
	}
	
	
	private function getDialectClass($dialect = null) {
		$dialect = $dialect = null ? "MySQL" : $dialect;
		
		return "PhpBURN_Dialect_$dialect";
	}
}
?>