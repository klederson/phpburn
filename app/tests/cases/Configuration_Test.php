<?php

phpBurn::load('Configuration');

/**
 * Testing Configuration
 **/
class Configuration_Test extends PHPUnit_Framework_TestCase {

	public function testDefaultOptions() {
		$conf = new phpBURN_Configuration(array());
		$this->assertType('phpBURN_Configuration', $conf);
	}

	public function testCustomOptions() {
		$custom_options = Array(
			// TODO
		);
		$conf = new phpBURN_Configuration($custom_options);
	}

}

?>
