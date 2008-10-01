<?php
require_once 'PHPUnit/Framework.php';

class ConfigTest extends PHPUnit_Framework_TestCase {
	function pending() {
		$this->markTestIncomplete();
	}
	
	function testIfExistsConfigFile() {
		$this->assertFileExists('../example_application/config.php');
	}
	
	function testIfExistsConfigVariable() {
		require('../example_application/config.php');
		$this->assertType('array', $thisConfig);
	}

	function testIfExistsDialectKeyInConfigVariable() {
		require('../example_application/config.php');
		$this->assertArrayHasKey('dialect', $thisConfig);
	}
	
	function testIfContentOfAttributeDialectIsMySQL() {
		require('../example_application/config.php');
		$this->assertEquals('MySQL', $thisConfig['dialect']);
	}

	function testIfExistsDatabaseKeyInConfigVariable() {
		require('../example_application/config.php');
		$this->assertArrayHasKey('database', $thisConfig);
	}

	function testIfContentOfAttributeDatabaseIsphpburn_test() {
		require('../example_application/config.php');
		$this->assertEquals('phpburn_test', $thisConfig['database']);
	}
	
	function testIfExistsUserKeyInConfigVariable() {
		require('../example_application/config.php');
		$this->assertArrayHasKey('user', $thisConfig);
	}
	
	function testIfContentOfAttributeUserIsphpburn() {
		require('../example_application/config.php');
		$this->assertEquals('phpburn', $thisConfig['user']);
	}

	function testIfExistsPasswordKeyInConfigVariable() {
		require('../example_application/config.php');
		$this->assertArrayHasKey('password', $thisConfig);
	}
	
	function testIfContentOfAttributePasswordIsphpburn() {
		require('../example_application/config.php');
		$this->assertEquals('phpburn', $thisConfig['password']);
	}

	function testIfExistsPortKeyInConfigVariable() {
		require('../example_application/config.php');
		$this->assertArrayHasKey('port', $thisConfig);
	}
	
	function testIfContentOfAttributeportIs3306() {
		require('../example_application/config.php');
		$this->assertEquals(3306, $thisConfig['port']);
	}
}
