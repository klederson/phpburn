<?php
/**
 * This carries all the configuration for each package
 * 
 * @package PhpBURN
 * @subpackage Configuration
 */
class phpBURN_ConfigurationItem {
	public $package;
	public $dialect;
	public $database;
	public $user;
	public $password;
	public $port;
	public $host;
	public $class_path;
	public $database_options = array();
	public $options = array();
	
	public function __construct($package,$packageOptions, array $config) {
		/**
		 * Automatic setup
		 */
		$class_attr = (get_class_vars(get_class($this)));
		foreach($class_attr as $key => $value) {
			$this->$key = $config[$key];
		}
		
		/**
		 * Defining config package owner
		 */
		$this->package = $package;
		
		/**
		 * Setting up the rest of configurations
		 * Particularities
		 */
		if(is_array($packageOptions) && count($packageOptions) > 0) {
			foreach($packageOptions as $key => $value) {
				$this->$key = $value;
			}
		}
		
		return $this;
	}
}
?>