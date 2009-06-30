<?php
/**
 * This class manages all internal messages into the system
 * from the LOG messages until EXCEPTIONS throught files, 
 * browser, database or even FirePHP
 * 
 * @author Kléderson Bueno <klederson@klederson.com>
 * @version 0.1a
 */

//Loading needed libs
PhpBURN::load('Extras.Views.Views');

//Load firephp only when we need
if(SYS_USE_FIREPHP == true) {
	PhpBURN::load('FirePHPCore.fb');
}

class PhpBURN_Message {
	
	/* MODE */
	const CONSOLE 					= 300001;
  	const BROWSER 				= 300002;
  	const FIREBUG 					= 300003;
  	const FILE    						= 300004;
  	const DATABASE    				= 300005;

  	/* TYPE */
  	const NOTICE						= '[!Notice!]';
  	const EXCEPTION				= '[!Exception!]';
  	const WARNING					= '[!Warning!]';
  	const LOG							= '[!Log!]';
  	const ERROR						= '[!Error!]';
  
  	/* STAGE */
  	public static $mode;
  	public static $fileName;
  	public static $messageLevel;
//  	@TODO IMPLEMENTS level using based by php.ini error levels
  	
  	/**
  	 * This is only used when used DATABASE MODE, should have be pre-configured
  	 * @var PhpBURN_Model
  	 */
  	public $databaseModel;
  	
  	public function setMode($mode = self::BROWSER) {
  		self::$mode = $mode;
  	}
  	
  	/**
  	 * To set databaseModel when used DATABASE MODE ON
  	 * 
  	 * @param PhpBURN_Model $databaseModel
  	 */
  	public function setDatabaseModel(PhpBURN_Model $databaseModel) {
  		self::$databaseModel = $databaseModel;
  	}
  	
  	public function setFileName($name) {
  		self::$fileName = $name;
  	}
  	
  	public function setMessageLevel($level = self::NOTICE) {
  		self::$messageLevel = $level;
  	}
  
  	/**
  	 * Main function of Messaging System
  	 * @param $originalMessage
  	 * @param $mode
  	 * @param $type
  	 * @return unknown_type
  	 */
  	public function output($originalMessage, $type = self::NOTICE) {
  		if(self::$mode == null) {
  			return false;
  		}
  		
  		//Now time
  		$time = mktime(date('H'),date('i'),date('s'),date('m'),date('d'), date('Y'));
  		$time = strftime(SYS_USE_DATEFORMAT,$time);
  		
  		//Usage
  		$usage = number_format(memory_get_usage()/1048576, 2, ',', ' ');

  		//Setup the message
  		$message = sprintf("%s: [%s (%s MB)] ",$type, $time, $usage);
  		$message .= var_export($originalMessage, true);
  		$message .="\r\n\r\n"; //The breaklines
  		
  		//Sending the message
  		switch(self::$mode) {
  			case self::BROWSER:
  				print PhpBURN_Views::lazyTranslate($message);
  			break;
  			case self::FIREBUG:
  				$message = PhpBURN_Views::lazyTranslate($message);
  				switch($type) {
  					case self::LOG:
  						FB::log($message);
  					break;
  					case self::WARNING:
  						FB::warn($message);
  					break;
  					case self::NOTICE:
  						FB::info($message);
  					break;
  					case self::ERROR:
  						FB::error($message);
  					break;
  					default:
  						FB::info($message);
  					break;
  				}
  			break;
  			case self::FILE:
  				$fileName = self::$fileName == null || !isset(self::$fileName) ? 'phpburn.log' : $this->fileName;
  				$fileName = SYS_BASE_PATH . $fileName;
  				  				
  				$fp = fopen($fileName, 'a+');
				fwrite($fp, PhpBURN_Views::lazyTranslate($message));
				fclose($fp);
				
				//@chmod($fileName, 0755);
  			break;
  			default:
  				print PhpBURN_Views::lazyTranslate($message);
  			break;
  		}
  		
  	  	if($type == self::EXCEPTION) {
  			throw new Exception($message);
  		}
  		
  		unset($time,$usage, $message,$translatedType);
  		
  	}
}

?>
