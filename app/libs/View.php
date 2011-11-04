<?php
/**
 * This class is to help users with views and translations
 * This class also requires gettext installed for work and the translations files must be .mo files compiled
 * 
 * USAGE:
 * 
 * The main resource here are the TOLKENS this are arrays with values that corresponds the value that should
 * be put into the view, for ex.:
 * 
 * $myTolkens['name'] = "John";
 * 
 * Than we should have in our html file this:
 * Hi, my name is [#name#]
 * 
 * Or in a advanced context
 * 
 * $myTolkens['user']['name'] = "John";
 * $myTolkens['product']['name'] = "Popcorn";
 * 
 * Than we should have in our html file this:
 * Hi, my name is [#user:name#] and I really like [#product:name#]
 * 
 * Than you can have INFINITE Dimensions but BE CAREFUL don't go mess your code, use it with inteligence and good sense
 * 
 * @package PhpBURN
 * @subpackage Views
 * 
 * @author Kléderson Bueno
 * @version 0.1-pre-alpha
 * @copyright Add4 Comunicação
 * @license GNU
 */
PhpBURN::load('Tools.Views.IView');
abstract class PhpBURN_Views implements IView {
	
	//Defines the main language of the view
	public static $lang = 'pt_BR';
	public static $domain = 'system';
	public static $translationFolder = 'locale/';
	
	public static $autoLoad = PHPBURN_VIEWS_AUTOLOAD;
        public static $viewMethod = null;
	
	public function autoLoad($status) {
		self::$autoLoad = $status;
	}
  
  public function setLang($lang = 'pt_BR', $domain = NULL, $translationFolder = NULL) {
    self::$lang = $lang;
    self::$domain = $domain == NULL ? self::$domain : $domain;
    self::$translationFolder = $translationFolder == NULL ? self::$translationFolder : $translationFolder;
  }
  
  public function getLang() {
    return self::$lang;
  }
	
	
	/**
	 * This function must translate the tolkens in the given string.
	 * In case of translation a language must be setted
	 *
	 * @param String $template
	 * @param Array $tokens
	 * @param String $lang
	 * 
	 * @version 2.0a
	 */
	public static function translateTokens($template, $tokens, $lang = null) {
		if(is_array($tokens)) {
			preg_match_all("|\[#(.*)#]|U",$template, $out, PREG_SET_ORDER);
			foreach($out as $index => $arrContent) {
				$pieces = explode(':',$arrContent[1]);
				for($i = 0; $i < count($pieces); $i++) {
					$stringArray .= "['$pieces[$i]']";
				}

				eval("\$_tmpValue = isset(\$tokens$stringArray)  ?  (string)\$tokens$stringArray : '__false__';");
				//print $stringArray . ':::::' . $_tmpValue . "\r\n"; //For hardcore debug usage

				if($_tmpValue != '__false__') {
					
					$contentValue = $lang == null ? $_tmpValue : self::translate($_tmpValue, $lang);
					$template = str_replace("[#$arrContent[1]#]",$contentValue,$template);
					
				}

				unset($stringArray,$_tmpValue,$contentValue);
			}
		} else {
			$template = preg_replace("/\[#*[A-Za-z0-9.:\-_,]*#\]/","",$template);
		}
		
		return $template;
	}
	
	/**
	 * This translates your content using GETTEXT and
	 *
	 * @param String $content
	 * @param String $lang
	 * @param String $domain
	 * @return String
	 * 
	 * @version 0.1a
	 */
	public static function translate($content, $lang = null, $domain = null) {
		//Auto-config setup
		$lang = $lang == null ? self::$lang : $lang;
		$domain = $domain == null ? self::$domain : $domain;
		
		//Set the enviroment var LANG
		putenv("LANG=$lang");
		//Set the locale to language
		setlocale(LC_ALL, $lang);
		//Set the domain file translation
		bindtextdomain($domain, SYS_BASE_PATH . self::$translationFolder);
		//Set the domain
		textdomain($domain);
		bind_textdomain_codeset($domain, 'UTF-8');
		
		$return = $content != "" && $content != null && !empty($content) ? _($content) : "";
		
		return $return;
	}
	
	/**
	 * A lazy translator. This can translate all your document without you have to worrie about it.
	 * You just have to put in your document the original content between TRANSLATE TAGS: [!...!] for example:
	 * [!home!] or [!Hello there, my name is!] and let this do the rest for you.
	 * 
	 * If you are using translations tags you MUST have to use this method.
	 */
	public static function lazyTranslate($content, $lang = null, $domain = null, $lazy = false) {
		preg_match_all("|\[!(.*)!]|U",$content, $out, PREG_SET_ORDER);
		foreach($out as $index => $arrContent) {
			$translation = self::translate($arrContent[1], $lang, $domain);
			
			$content = str_replace("[!$arrContent[1]!]",$translation,$content);
			unset($translation);
		}
		
		return $content;
	}
	
	public function loadView($viewName, $data, $toVar = false) {
		//Getting the path view
		$viewPath = SYS_VIEW_PATH . DS . $viewName . '.' . SYS_VIEW_EXT;
		
		if(file_exists($viewPath)) {
			PhpBURN_Message::output('[!Loading view:!] ' . $viewPath);
			$output = self::processViewData($viewPath, $data);
			unset($viewFile, $viewName, $viewPath);
			return $toVar == false ? print $output : $output;
		} else {
			return false;
		}
	}

        public function loadViewFile($path, $data, $toVar = false) {

		if(file_exists($path)) {
			PhpBURN_Message::output('[!Loading view file:!] ' . $path);
			$output = self::processViewData($path, $data);
			unset($path, $path);
			return $toVar == false ? print $output : $output;
		} else {
			return false;
		}
	}
	
	/**
	 * This method is used to process the data into the view and than return it to the main method that will handle what to do.
	 * It also uses buffer to handle that content.
	 * 
	 * @author Klederson Bueno <klederson@klederson.com>
	 * @version 0.1a
	 * 
	 * @param String $___phpBurnFilePath
	 * @param Array $__phpBurnData
	 * @return String
	 */
	private function processViewData($___phpBurnFilePath, $__phpBurnData) {
            $viewProcess = self::chooseViewMethod();

            return $viewProcess->processViewData($___phpBurnFilePath, $__phpBurnData);
	}

        public function chooseViewMethod() {
            self::$viewMethod = !defined('PHPBURN_VIEWS_METHOD') || !empty(self::$viewMethod) ? self::$viewMethod : PHPBURN_VIEWS_METHOD;
            if(self::$viewMethod == null) {
                self::$viewMethod = 'default';
            }
            PhpBURN::load('Tools.Views.'.self::$viewMethod);
            $classString = sprintf("%s_PhpBURN_ViewProcess",self::$viewMethod);
            
            return new $classString;
        }

        public function setViewMethod($method) {
            self::$viewMethod = $method;
        }
	
	#####################################################
	# Inteligent View Methods
	#####################################################
	
	private function loadInteligentView($template, $tokens, $lang = null) {
		if(is_array($tokens)) {
			preg_match_all("|\[#(.*)#]|U",$template, $out, PREG_SET_ORDER);
			foreach($out as $index => $arrContent) {
				$pieces = explode(':',$arrContent[1]);
				for($i = 0; $i < count($pieces); $i++) {
					$stringArray .= "['$pieces[$i]']";
				}

				eval("\$_tmpValue = isset(\$tokens$stringArray)  ?  (string)\$tokens$stringArray : '__false__';");
				//print $stringArray . ':::::' . $_tmpValue . "\r\n"; //For hardcore debug usage

				if($_tmpValue != '__false__') {
					
					$contentValue = $lang == null ? $_tmpValue : self::translate($_tmpValue, $lang);
					$template = str_replace("[#$arrContent[1]#]",$contentValue,$template);
					
				}

				unset($stringArray,$_tmpValue,$contentValue);
			}
		} else {
			$template = preg_replace("/\[#*[A-Za-z0-9.:\-_,]*#\]/","",$template);
		}
		
		return $template;
	}
}
?>