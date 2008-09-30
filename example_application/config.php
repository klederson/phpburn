<?php
/**
 * This file has been designed to auto-config all your
 * path variables like path, url and others automaticaly
 * for this it have to stay the same folder as your index.php
 * 
 * If you want to put it in another place you should to config manualy
 * or modify our auto-generate code.
 * 
 * @author ADD4 Comunicação ( www.add4.com.br )
 * @version 1.0
 */

$base_url = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on") ? "https" : "http");
$base_url .= "://".$_SERVER['HTTP_HOST'];
$base_url .= str_replace(basename($_SERVER['SCRIPT_NAME']),"",$_SERVER['SCRIPT_NAME']);

if (function_exists('realpath') AND @realpath(dirname(__FILE__)) !== FALSE)
{
	$system_folder = realpath(dirname(__FILE__));
}

define('SYS_BASE_URL',$base_url,true);
define('SYS_BASE_PATH',$system_folder,true);
define('SYS_CSS_PATH',$system_folder . '/public/css/',true);
define('SYS_IMAGE_PATH',$system_folder . '/public/images/',true);
define('SYS_JAVASCRIPT_PATH',$system_folder . '/public/js/',true);
define('SYS_FILE_PATH',$system_folder . '/public/files/',true);

$thisConfig = array(
		/**
		 * Database configuration ( Default )
		 * 
		 * This configuration will be used by defaul when
		 * package configs are not setted up
		 */
		'dialect' => 'MySQL',
		'database' => 'webinsys',
		'user' => 'phpburn_example',
		'password' => 'phpburn_example_pass',
		'port' => '3306',
		'host' => 'localhost',

		/**
		 * Structure configuration
		 */
		'class_path' => SYS_BASE_PATH . DIRECTORY_SEPARATOR . 'model' . DIRECTORY_SEPARATOR,

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

		/* ---------------------PACKAGES--------------------- */
		/**
		 * Here we setup all our packages information
		 * If you want to use the default configuration above
		 * you just create:
		 * 
		 * @example 'package' => array('packageone','packagetwo',.......);
		 * 
		 * Or if you want to create some different config like another database
		 * or another host,port,user,password or even another Dialect you can use like this.
		 * 
		 * @example 'package' => array(
		 * 'packageone',
		 * 'packagetwo',
		 * 'packagethree' => array(
		 *   'host' => 'sqlitefile',
		 *	 'dialect' => 'SQLITE',
		 *	 'database' => 'phpburn',
		 *	 'class_path' => '/home/models/phpburn/',
		 *	 'port' => '3000'
		 *  ),
		 * 'packagefour' => array(
		 *   'host' => 'mssqlhost.com',
		 *	 'dialect' => 'MSSQL',
		 *	 'database' => 'microsoftclass',
		 *	 'class_path' => '/home/models/microsoftclass/',
		 *	 'port' => '666'
		 *  ),
		 *  'packagefive',
		 *  ...
		 * )
		 */
			
			'packages' => array(
				'webinsys',

				'phpburn' => array(
					'host' => 'uol.com.br',
					'dialect' => 'SQLITE',
					'database' => 'phpburn',
					'class_path' => '/home/models/phpburn/',
					'port' => '3000'
				),
				
				'newmodel',
			)
);
?>