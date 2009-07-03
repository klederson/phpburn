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
			PhpBURN_Message::output('[!Empty database into configuration!]',PhpBURN_Message::EXCEPTION);
		}
		if(empty($options['user']))
		{
			PhpBURN_Message::output('[!Empty database user into configuration!]',PhpBURN_Message::EXCEPTION);
		}
		if(empty($options['password']))
		{
			PhpBURN_Message::output('[!Empty password into configuration!]',PhpBURN_Message::EXCEPTION);
		}
		if(empty($options['class_path']))
		{
			PhpBURN_Message::output('[!Empty class_path into configuration!]',PhpBURN_Message::EXCEPTION);
		}
		
		/**
		 * Auto-configurable options
		 */
		if(empty($options['dialect']))
		{
			PhpBURN_Message::output('[!Empty dialect into configuration!]',PhpBURN_Message::WARNING);
			$options['dialect'] = 'MySQL';
		}
		
		if(empty($options['port']))
		{
			PhpBURN_Message::output('[!Empty database port into configuration!]',PhpBURN_Message::WARNING);
			$options['port'] = '3306';
		}
		if(empty($options['host']))
		{
			PhpBURN_Message::output('[!Empty database host into configuration!]',PhpBURN_Message::WARNING);
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
	
	/**
	 * Gets the config about a specific package
	 * 
	 * @param String $package
	 * @return Array
	 */
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