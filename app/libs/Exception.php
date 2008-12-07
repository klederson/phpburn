<?php
PhpBURN::load('Exception.IException');

class PhpBURN_Exception extends Exception implements IException {

	public function debug(PhpBURN_Message $msg = null) {
		var_dump($msg);
	}
	
	public function error(PhpBURN_Message $msg = null) {
		var_dump($msg);
	}

	public function warning(PhpBURN_Message $msg = null) {
		var_dump($msg);
	}

}

?>
