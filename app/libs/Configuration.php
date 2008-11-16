<?php
PhpBURN::load('Configuration.ConfigurationItem');
class PhpBURN_Configuration {
	
	public static $options = array();
	private $connection = null;
	
	public function __construct(array $options) {
		/*
		 * Fatal Errors
		 */
		if(empty($options['database']))
		{
			throw new PhpBURN_Exception(PhpBURN_Message::EMPTY_DATABASE);
			return;
		}
		if(empty($options['user']))
		{
			throw new PhpBURN_Exception(PhpBURN_Message::EMPTY_DATABASE_USER);
			return;
		}
		if(empty($options['password']))
		{
			throw new PhpBURN_Exception(PhpBURN_Message::EMPTY_DATABASE_PASSWORD);
			return;
		}
		if(empty($options['class_path']))
		{
			throw new PhpBURN_Exception(PhpBURN_Message::EMPTY_CLASSPATH);
			return;
		}
		
		/**
		 * Auto-configurable options
		 */
		if(empty($options['dialect']))
		{
			PhpBURN_Logs::debug(PhpBURN_Message::EMPTY_DIALECT);
			$options['dialect'] = 'MySQL';
		}
		
		if(empty($options['port']))
		{
			PhpBURN_Logs::debug(PhpBURN_Message::EMPTY_DATABASE_PORT);
			$options['port'] = '3306';
		}
		if(empty($options['host']))
		{
			PhpBURN_Logs::debug(PhpBURN_Message::EMPTY_DATABASE_HOST);
			$options['host'] = 'localhost';
		}
		
		/**
		 * Search for all package in that Driver Connection/Configuration and
		 * create specific configurations for them.
		 * 
		 * Specific configurations
		 * database - package can use same database conection and use another database
		 * class_path - package can be into another class_path ( full path )
		 */
		foreach($options['packages'] as $key => $value) {
			$key = is_array($value) ? $key : $value;
			self::$options[$key] = new PhpBURN_ConfigurationItem($key,$value,$options);
		}
	}
	
	public function getConfig($package = null) {
		if($package == null) {
			return self::$options;
		} else {
			$arrPackage = explode('.',$package);
			$package = count($arrPackage) > 0 ? $arrPackage[0] : $package;
			return self::$options[$package];
		}
	}
}

?>