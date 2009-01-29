<?php
PhpBURN::load('Exception.IException');
PhpBURN::load('Message');
require_once('FirePHPCore/fb.php');
ob_start();

class PhpBURN_Exception implements IException 
{

  const CONSOLE = '10001';
  const BROWSER = '10002';
  const FIREBUG = '10003';
  const FILE    = '10004';
  
  private $message;

	public function log($message)
	{
	  $message = "[" .date('d/m/Y H:i:s') ."] - (" .number_format(memory_get_usage()/1048576, 2, ',', ' ') ." MB) - " .$message;
	  $messageObj = new PhpBURN_Message($message);
	  $this->setMessage($messageObj);
	}
	
	public function debug($message)
	{
	  $message = "[" .date('d/m/Y H:i:s') ."] - (" .number_format(memory_get_usage()/1048576, 2, ',', ' ') ." MB) - " .$message;
	  $messageObj = new PhpBURN_Message($message);
	  $this->setMessage($messageObj);
	}
	
	public function warning($message)
	{
		$message = "[" .date('d/m/Y H:i:s') ."] - (" .number_format(memory_get_usage()/1048576, 2, ',', ' ') ." MB) - " .$message;
    $messageObj = new PhpBURN_Message($message);
		$this->setMessage($messageObj);
	}

	public function error($message)
	{
		$message = "[" .date('d/m/Y H:i:s') ."] - (" .number_format(memory_get_usage()/1048576, 2, ',', ' ') ." MB) - " .$message;
		$messageObj = new PhpBURN_Message($message);
		$this->setMessage($messageObj);
	}
	
	public function setMessage(PhpBURN_Message $message = null) {
	  $this->message = $message;
	}
	
	public function setOutput($mode) {
	  $this->output = $mode;
	  return true;
	}
	
	public function output() {
	  return $this->message->getMessage();
	}
}
?>

