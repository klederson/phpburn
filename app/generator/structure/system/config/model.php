<?php
################################
# Model Settings
################################
$thisConfig = array(
		/**
		 * Database configuration ( Default )
		 * 
		 * This configuration will be used by defaul when
		 * package configs are not setted up
		 */
		'dialect' => 'MySQL',
		'database' => '[#setup:database#]',
		'user' => '[#setup:username#]',
		'password' => '[#setup:password#]',
		'port' => '3306',
		'host' => '[#setup:host#]',

		/**
		 * Structure configuration
		 */
		'class_path' => SYS_MODEL_PATH,

		/**
		 * database-options changes according to the database used
		 * So if you want to know what options have each database please
		 * look at documentation in section Dialects
		 * 
		 * OPTIONAL
		 */
		'database_options' => array(),

		/**
		 * options are general configs for the project
		 * See Configuration section at documentation for more information
		 * 
		 * OPTIONAL
		 */
		'options' => array(),
			
			'packages' => array(
				'phpburn'
			)
);

//Loading the configuration file
$config = new PhpBURN_Configuration($thisConfig);

$configItems = $config->getConfig();

foreach($configItems as $package => $infos) {
    $path = $infos->class_path . $package . DS;
    set_include_path(get_include_path() . ': '.$path);
}

spl_autoload_register(null, false);
spl_autoload_extensions(SYS_MODEL_EXT);
spl_autoload_register('PhpBURN::import');
?>