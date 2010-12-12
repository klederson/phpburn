<?php
PhpBURN::load('Configuration.ConfigurationItem');

/**
 * @package PhpBURN Configuration
 * @author Kléderson Bueno <klederson@klederson.com>
 */
class PhpBURN_Configuration {
	
	public static $options = array();
	private $connection = null;
	
	public function __construct(array $options) {
		$default_options = Array(
			'database' => '',
			'user' => '',
			'password' => '',
			'class_path' => '',
			'dialect' => 'MySQL',
			'port' => 3306,
			'host' => 'localhost',
			'packages' => array()
		);
                
		$options = array_merge($default_options, $options);

		/*
		 * Fatal Errors
		 */
		if(empty($options['database']))
                    PhpBURN_Message::output('[!Empty database into configuration!]',PhpBURN_Message::ERROR);

		if(empty($options['user']))
                    PhpBURN_Message::output('[!Empty database user into configuration!]',PhpBURN_Message::ERROR);

                if(empty($options['password']))
                    PhpBURN_Message::output('[!Empty password into configuration!]',PhpBURN_Message::ERROR);
		
		if(empty($options['class_path']))
                    PhpBURN_Message::output('[!Empty class_path into configuration!]',PhpBURN_Message::ERROR);
				
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
			self::$options[$key] = new phpBURN_ConfigurationItem($key, $value, $options);
		}
	}
	
	/**
	 * Gets the config about a specific package
	 * 
	 * @param String $package
	 * @return Array
	 */
	public static function getConfig($package = null) {
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
