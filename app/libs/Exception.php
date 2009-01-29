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
  private $output;

	public function log($message)
	{
	  $message = "[" .date('d/m/Y H:i:s') ."] - (" .number_format(memory_get_usage()/1048576, 2, ',', ' ') ." MB) - " .$message;
	  $messageObj = new PhpBURN_Message($message);
	  $this->firebugMode = FirePHP::LOG;
	  $this->setMessage($messageObj);
	  return $this->output();
	}
	
	public function debug($message)
	{
	  $message = "[" .date('d/m/Y H:i:s') ."] - (" .number_format(memory_get_usage()/1048576, 2, ',', ' ') ." MB) - " .$message;
	  $messageObj = new PhpBURN_Message($message);
	  $this->firebugMode = FirePHP::INFO;
	  $this->setMessage($messageObj);
	  return $this->output();
	}
	
	public function warning($message)
	{
		$message = "[" .date('d/m/Y H:i:s') ."] - (" .number_format(memory_get_usage()/1048576, 2, ',', ' ') ." MB) - " .$message;
    $messageObj = new PhpBURN_Message($message);
    $this->firebugMode = FirePHP::WARN;
		$this->setMessage($messageObj);
		return $this->output();
	}

	public function error($message)
	{
		$message = "[" .date('d/m/Y H:i:s') ."] - (" .number_format(memory_get_usage()/1048576, 2, ',', ' ') ." MB) - " .$message;
		$messageObj = new PhpBURN_Message($message);
		$this->firebugMode = FirePHP::ERROR;
		$this->setMessage($messageObj);
		return $this->output();
	}
	
	public function setMessage(PhpBURN_Message $message = null) {
	  $this->message = $message;
	}
	
	public function setOutput($mode, $path = '') {
	  $this->output = $mode;
	  return true;
	}
	
	public function output() {
	  switch($this->output) {
	    case self::BROWSER:
	      print $this->message->getMessage();
      break;
      case self::FIREBUG:
				$firephp = FirePHP::getInstance(true);
				$firephp->fb($this->message->getMessage(), $this->firebugMode);
      break;
      case self::CONSOLE:
        print $this->message->getMessage();
      break;
      case self::FILE:
        print $this->message->getMessage();
      break;
      default:
        return $this->message->getMessage();
      break;
	  }
	}
}
?>

