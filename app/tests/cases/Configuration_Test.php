<?php

phpBurn::load('Configuration');

/**
 * Testing Configuration
 **/
class Configuration_Test extends PHPUnit_Framework_TestCase {

	public function testIfRequiredOptionsThrowsExceptions() {

		$this->markTestSkipped();

		phpBURN_Message::setMode(phpBURN_Message::CONSOLE);
		// database required
		ob_start();
		try {
			$Configuration = new phpBURN_Configuration(array('packages' => array('app')));
			$this->assertTrue(false, 'Should never hit this line');
		} catch(Exception $e) {
			$msg = ob_get_contents();
			$this->assertRegExp('/Exception/', $msg);
		};

		// user required
		try {
			$Configuration = new phpBURN_Configuration(array('database' => 'somedb', 'packages' => array('app')));
			$this->assertTrue(false, 'Should never hit this line');
		} catch (Exception $e) {};
			$msg = ob_get_contents();
			$this->assertRegExp('/Exception/', $msg);

		// password required
		try {
			$Configuration = new phpBURN_Configuration(array(
				'database' => 'somedb',
				'user' => 'someuser',
				'packages' => array('app')
			));
			$this->assertTrue(false, 'Should never hit this line');
		} catch (Exception $e) {
			$msg = ob_get_contents();
			$this->assertRegExp('/Exception/', $msg);
		}

		
		// class_path required
		try {
			$Configuration = new phpBURN_Configuration(array(
				'database' => 'somedb',
				'user' => 'someuser',
				'password' => 'somepasswd',
				'packages' => array('app')
			));
		} catch (Exception $e) {
			$msg = ob_get_contents();
			$this->assertRegExp('/Exception/', $msg);
		}
		ob_end_clean();
	}

	public function testIfOptionsAreSet() {
		$Configuration = new phpBURN_Configuration(Array(
			'database' => 'somedb',
			'user' => 'someuser',
			'password' => 'somepasswd',
			'class_path' => 'class_path',
			'packages' => array('app')
		));
		$this->assertInstanceOf('phpBURN_Configuration', $Configuration);
		$ConfigItem = $Configuration->getConfig('app');
		$this->assertEquals($ConfigItem->package, 'app');
		$this->assertEquals($ConfigItem->dialect, 'MySQL');
		$this->assertEquals($ConfigItem->database, 'somedb');
		$this->assertEquals($ConfigItem->user, 'someuser');
		$this->assertEquals($ConfigItem->password, 'somepasswd');
		$this->assertEquals($ConfigItem->class_path, 'class_path');
		$this->assertEquals($ConfigItem->port, '3306');
		$this->assertEquals($ConfigItem->host, 'localhost');
		$this->assertEquals($ConfigItem->database_options, array());
		$this->assertEquals($ConfigItem->options, array());
	}

	public function testCustomOptions() {
		$Configuration = new phpBURN_Configuration(Array(
			'database' => 'somedb',
			'user' => 'someuser',
			'password' => 'somepasswd',
			'class_path' => 'class_path',
			'packages' => array('app' => Array(
				'database' => 'someotherdb',
				'user' => 'someotheruser',
				'password' => 'someotherpasswd',
				'class_path' => 'other_class_path',
				'host' => '127.0.0.1'
			))
		));
		$this->assertInstanceOf('phpBURN_Configuration', $Configuration);
		$ConfigItem = $Configuration->getConfig('app');
		$this->assertEquals($ConfigItem->package, 'app');
		$this->assertEquals($ConfigItem->dialect, 'MySQL');
		$this->assertEquals($ConfigItem->database, 'someotherdb');
		$this->assertEquals($ConfigItem->user, 'someotheruser');
		$this->assertEquals($ConfigItem->password, 'someotherpasswd');
		$this->assertEquals($ConfigItem->class_path, 'other_class_path');
		$this->assertEquals($ConfigItem->port, '3306');
		$this->assertEquals($ConfigItem->host, '127.0.0.1');
		$this->assertEquals($ConfigItem->database_options, array());
		$this->assertEquals($ConfigItem->options, array());
	}

}

?>
