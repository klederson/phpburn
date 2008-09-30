<?php
class PhpBURN_Connection
{
	private static $connections = array();
	
	public function create(PhpBURN_ConfigurationItem $config) {
		$conn = $this->getConnection($config->dialect);
		if(!$conn) {
			//Create a new connection
			
			//Loads the interface for dialect uses
			PhpBURN::load('Connection.IConnection');
			
			if(PhpBURN::load("Connection.$config->dialect") != "error") {
				$className = $this->getConnectionClass($config->dialect);
				$connectionClass = new $className;
				
				$connectionClass->setHost($config->host);
				$connectionClass->setPort($config->port);
				$connectionClass->setUser($config->user);
				$connectionClass->setPassword($config->password);
				$connectionClass->setDatabase($config->database);
				
				//$connectionClass->setOptions($config->options);
				
				$conn = self::$connections[$config->package] = $connectionClass;
				
			} else {
				exit();
			}
		}
		
		return $conn;
	}
	
	public function getConnection($package = null) {
		if(!isset(self::$connections[$package])) {
			return false;
		} else {
			return self::$connections[$package];
		}
	}
	
	private function getConnectionClass($dialect = null) {
		$dialect = $dialect = null ? "MySQL" : $dialect;
		
		return "PhpBURN_Connection_$dialect";
	}
}
?>