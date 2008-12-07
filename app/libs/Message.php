<?php

class PhpBURN_Message {
	const EMPTY_DATABASE = 10;
	const EMPTY_DATABASE_USER = 11;
	const EMPTY_DATABASE_PASSWORD = 12;
	const EMPTY_CLASSPATH = 13;
	const EMPTY_DIALECT = 14;
	const EMPTY_DATABASE_PORT = 15;
	const EMPTY_DATABASE_HOST = 16;
	const EMPTY_PACKAGEORTABLE = 17;

	public $message = null;

	function __construct($msg) {
		switch ($msg) {
			case EMPTY_DATABASE:
				$m = _('Database option is empty.');
				break;
			
			case EMPTY_DATABASE_USER:
				$m = _('Database user option is empty.');
				break;
			
			case EMPTY_DATABASE_PASSWORD:
				$m = _('Database password option is empty.');
				break;
			
			case EMPTY_CLASSPATH:
				$m = _('Classpath option is empty.');
				break;
			
			case EMPTY_DIALECT:
				$m = _('Dialect option is empty.');
				break;
			
			case EMPTY_DATABASE_PORT:
				$m = _('Database port is empty.');
				break;
			
			case EMPTY_DATABASE_HOST:
				$m = _('Database host is empty.');
				break;
			
			case EMPTY_PACKAGEORTABLE:
				$m = _('Package or table option is empty.');
				break;
			default:
				$m = _('Unknown message.');
				break;
		}
		$this->message = $m;
	}

	public function getMessage() {
		return $this->message;
	}

}

?>
