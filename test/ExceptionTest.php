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
    $exception->log("LOG");
    $message = "[" .date('d/m/Y H:i:s') ."] - (" .number_format(memory_get_usage()/1048576, 2, ',', ' ') ." MB) - LOG";
    $this->assertRegExp("/\[[0-9]{2}\/[0-9]{2}\/[0-9]{4} [0-9]{2}:[0-9]{2}:[0-9]{2}\] - \([0-9]{1,2},[0-9]{1,2} MB\) - [A-Za-z0-9]*/", $exception->output());
  }

  function testShowDebugMessage() {
    $exception = new PhpBurn_Exception();
    $exception->debug("DEBUG");
    $message = "[" .date('d/m/Y H:i:s') ."] - (" .number_format(memory_get_usage()/1048576, 2, ',', ' ') ." MB) - DEBUG";
    $this->assertRegExp("/\[[0-9]{2}\/[0-9]{2}\/[0-9]{4} [0-9]{2}:[0-9]{2}:[0-9]{2}\] - \([0-9]{1,2},[0-9]{1,2} MB\) - [A-Za-z0-9]*/", $exception->output());
  }

  function testShowWarningMessage() {
    $exception = new PhpBurn_Exception();
    $exception->warning("WARNING");
    $message = "[" .date('d/m/Y H:i:s') ."] - (" .number_format(memory_get_usage()/1048576, 2, ',', ' ') ." MB) - WARNING";
    $this->assertRegExp("/\[[0-9]{2}\/[0-9]{2}\/[0-9]{4} [0-9]{2}:[0-9]{2}:[0-9]{2}\] - \([0-9]{1,2},[0-9]{1,2} MB\) - [A-Za-z0-9]*/", $exception->output());
  }

  function testShowErrorMessage() {
    $exception = new PhpBurn_Exception();
    $exception->error("ERROR");
    $message = "[" .date('d/m/Y H:i:s') ."] - (" .number_format(memory_get_usage()/1048576, 2, ',', ' ') ." MB) - ERROR";
    $this->assertRegExp("/\[[0-9]{2}\/[0-9]{2}\/[0-9]{4} [0-9]{2}:[0-9]{2}:[0-9]{2}\] - \([0-9]{1,2},[0-9]{1,2} MB\) - [A-Za-z0-9]*/", $exception->output());
  }
  function testSetTypeMessage() {
    $exception = new PhpBurn_Exception();
    $this->assertTrue($exception->setOutput(PhpBurn_Exception::CONSOLE));
  }
}
?>
