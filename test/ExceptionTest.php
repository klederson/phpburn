<?php
require_once 'PHPUnit/Framework.php';
require_once('../app/libs/Exception.php');
require_once('../app/libs/Message.php');

class ExceptionTest extends PHPUnit_Framework_TestCase {
  function testCreateANewExceptionObject() {
    $this->assertType('PhpBurn_Exception', new PhpBurn_Exception());
  }
  
  function testShowLogMessage() {
    $exception = new PhpBurn_Exception();
    $this->assertRegExp("/\[[0-9]{2}\/[0-9]{2}\/[0-9]{4} [0-9]{2}:[0-9]{2}:[0-9]{2}\] - \([0-9]{1,2},[0-9]{1,2} MB\) - [A-Za-z0-9]*/", $exception->log("LOG"));
  }

  function testShowDebugMessage() {
    $exception = new PhpBurn_Exception();
    $this->assertRegExp("/\[[0-9]{2}\/[0-9]{2}\/[0-9]{4} [0-9]{2}:[0-9]{2}:[0-9]{2}\] - \([0-9]{1,2},[0-9]{1,2} MB\) - [A-Za-z0-9]*/", $exception->debug("DEBUG"));
  }

  function testShowWarningMessage() {
    $exception = new PhpBurn_Exception();
    $this->assertRegExp("/\[[0-9]{2}\/[0-9]{2}\/[0-9]{4} [0-9]{2}:[0-9]{2}:[0-9]{2}\] - \([0-9]{1,2},[0-9]{1,2} MB\) - [A-Za-z0-9]*/", $exception->warning("WARNING"));
  }

  function testShowErrorMessage() {
    $exception = new PhpBurn_Exception();
    $this->assertRegExp("/\[[0-9]{2}\/[0-9]{2}\/[0-9]{4} [0-9]{2}:[0-9]{2}:[0-9]{2}\] - \([0-9]{1,2},[0-9]{1,2} MB\) - [A-Za-z0-9]*/", $exception->error("ERROR"));
  }
  function testSetTypeMessage() {
    $exception = new PhpBurn_Exception();
    $this->assertTrue($exception->setOutput(PhpBurn_Exception::CONSOLE));
  }
}
?>
