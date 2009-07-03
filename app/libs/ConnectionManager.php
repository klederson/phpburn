<?php
/**
 * ConnectionManager Class
 * 
 * This class manage the conection for each package.
 * Can be used by many ways but by default its better if you simply do not mess around ;) let the application work for you.
 * 
 * @author Kléderson Bueno <klederson@klederson.com>
 * @version 0.1a
 *
 */
class PhpBURN_ConnectionManager
{
	/**
	 * This variable storage in runtime all dialects for each kind of package. For more details 
	 * @see getConnection()
	 * @var Array
	 */
	private static $connections = array();
	
	/**
	 * Creates a new Connector or retreive the existing one based on the Configuration.
	 * 
	 * @param PhpBURN_ConfigurationItem $config
	 * @return PhpBURN_Connection
	 */
	public function create(PhpBURN_ConfigurationItem &$config) {
		$conn = self::getConnection($config->package);
		if(!$conn) {
			//Create a new connection
			
			//Loads the interface for dialect uses
			PhpBURN::load('Connection.IConnection');
			
			if(PhpBURN::load("Connection.$config->dialect") != "error") {
				$className = self::getConnectionClass($config->dialect);
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
	
	/**
	 * Retreive the current connector for each package
	 * 
	 * @param String $package
	 * @return PhpBURN_Connection
	 * @return Boolean
	 */
	public function getConnection($package = null) {
		if(!isset(self::$connections[$package])) {
			return false;
		} else {
			return self::$connections[$package];
		}
	}
	
	/**
	 * Discover the correspondent class for the Connection
	 * 
	 * @param String $dialect
	 * @return PhpBURN_Connection
	 */
	private function getConnectionClass($dialect = null) {
		$dialect = $dialect = null ? "MySQL" : $dialect;
		
		return "PhpBURN_Connection_$dialect";
	}
}
?>