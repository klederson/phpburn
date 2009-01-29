<?php

class PhpBURN_Message {
	const LOG = 0;
	const DEBUG = 1;
	const WARNING = 2;
	const ERROR = 3;
	const EMPTY_DATABASE = 10;
	const EMPTY_DATABASE_USER = 11;
	const EMPTY_DATABASE_PASSWORD = 12;
	const EMPTY_CLASSPATH = 13;
	const EMPTY_DIALECT = 14;
	const EMPTY_DATABASE_PORT = 15;
	const EMPTY_DATABASE_HOST = 16;
	const EMPTY_PACKAGEORTABLE = 17;

	public $message = null;

	function __construct($message) {
		switch ($message) {
			case EMPTY_DATABASE:
				$msg = 'Database option is empty.';
			break;
			case EMPTY_DATABASE_USER:
				$msg = 'Database user option is empty.';
			break;
			case EMPTY_DATABASE_PASSWORD:
				$msg = 'Database password option is empty.';
			break;
			case EMPTY_CLASSPATH:
				$msg = 'Classpath option is empty.';
			break;
			case EMPTY_DIALECT:
				$msg = 'Dialect option is empty.';
			break;
			case EMPTY_DATABASE_PORT:
				$msg = 'Database port is empty.';
			break;
			case EMPTY_DATABASE_HOST:
  			$msg = 'Database host is empty.';
			break;
			case EMPTY_PACKAGEORTABLE:
				$msg = 'Package or table option is empty.';
			break;
		}
		$this->message = $message;
	}
	public function getMessage() {
		return $this->message;
	}
}

?>
