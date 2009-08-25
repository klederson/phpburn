<?php

//Load firephp only when we need
if(SYS_USE_FIREPHP == true) {
	PhpBURN::load('Tools.Extras.FirePHPCore.fb');
}

/**
 * This class manages all internal messages into the system
 * from the LOG messages until EXCEPTIONS throught files, 
 * browser, database or even FirePHP
 * 
 * @package PhpBURN
 * @subpackage Messages
 * 
 * @author Kléderson Bueno <klederson@klederson.com>
 * @version 0.1a
 */
class PhpBURN_Message {
	
	/* MODE */
	const CONSOLE 					= 300001;
  	const BROWSER 				= 300002;
  	const FIREBUG 					= 300003;
  	const FILE    						= 300004;
  	const DATABASE    			= 300005;

  	/* TYPE */
  	const NOTICE						= '[!Notice!]';
  	const EXCEPTION				= '[!Exception!]';
  	const WARNING					= '[!Warning!]';
  	const LOG							= '[!Log!]';
  	const ERROR						= '[!Error!]';
  	
  	/* PRIORITY */
  	const LOW							= 300100;
  	const NORMAL					= 300101;
  	const HIGH						= 300102;
  	const INSANE						= 300103; //this one will ALWAYS be show, even when the message type is not allowed (will not only when message system is turned off)
  
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
  	
  	/**
  	 * Turn the Message system on and set the mode
  	 * 
  	 * @param Integer $mode
  	 */
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
  	
  	/**
  	 * Defines the name for the log file
  	 * 
  	 * @param String $name
  	 */
  	public function setFileName($name) {
  		self::$fileName = $name;
  	}
  	
  	
  	public function setMessageLevel($level = self::NOTICE) {
  		self::$messageLevel = $level;
  	}
  
  	/**
  	 * Main function of Messaging System. It outputs a message based on the mode defined.
  	 * 
  	 * @param String $originalMessage
  	 * @param String $type
  	 * @return String
  	 * @return Boolean
  	 */
  	public function output($originalMessage, $type = self::NOTICE, $priority = self::NORMAL) {
  		if(self::$mode == null) {
  			return false;
  		}
  		
  		if($type == null) {
  			$type = self::NOTICE;
  		}
  		
  		//Searching if Views is loaded
		if(array_search('PhpBURN_Views',get_declared_classes()) == true) {
			$messageClass = 'PhpBURN_Views';			
		} else {
			$messageClass = self;
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
  				print $message = call_user_func(array($messageClass,'lazyTranslate'),$message);
  			break;
  			case self::FIREBUG:
  				$message = call_user_func(array($messageClass,'lazyTranslate'),$message);
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
				fwrite($fp, call_user_func(array($messageClass,'lazyTranslate'),$message));
				fclose($fp);
				
				//@chmod($fileName, 0755);
  			break;
  			default:
  				print $message = call_user_func(array($messageClass,'lazyTranslate'),$message);
  			break;
  		}
  		
  	  	if($type == self::EXCEPTION) {
  			throw new Exception($message);
  		}
  		
  		unset($time,$usage, $message,$translatedType);
  		
  	}
  	
	public static function lazyTranslate($content, $lang = null, $domain = null, $lazy = false) {
		preg_match_all("|\[!(.*)!]|U",$content, $out, PREG_SET_ORDER);
		foreach($out as $index => $arrContent) {
			$content = str_replace("[!$arrContent[1]!]",$arrContent[1],$content);
		}
		
		return $content;
	}
}

?>
