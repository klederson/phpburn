<?php
$thisConfig = array(
		/**
		 * Database configuration ( Default )
		 * 
		 * This configuration will be used by defaul when
		 * package configs are not setted up
		 */
		'dialect' => 'MySQL',
		'database' => 'phpburn_test',
		'user' => 'phpburn',
		'password' => 'phpburn',
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
		 * @example 
		 * <code>
		 * 'package' => array(
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
		 *  'packagefive'
		 * )
		 * </code>
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