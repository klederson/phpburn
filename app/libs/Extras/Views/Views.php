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
 * @author Kléderson Bueno
 * @version 0.1-pre-alpha
 * @copyright Add4 Comunicação
 * @license GNU
 */
require_once('IViews.php');
abstract class PhpBURN_Views implements IViews {
	
	//Defines the main language of the view
	public static $lang = 'pt_BR';
	public static $domain = 'system';
	public static $translationFolder = 'locale/';
	
	
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
		if(is_array($tolkens)) {
			preg_match_all("|\[#(.*)#]|U",$template, $out, PREG_SET_ORDER);
			foreach($out as $index => $arrContent) {
				$pieces = explode(':',$arrContent[1]);
				for($i = 0; $i < count($pieces); $i++) {
					$stringArray .= "[$pieces[$i]]";
				}
				eval("\$_tmpValue = \$tokens$stringArray;");
								
				$contentValue = $lang == null ? $_tmpValue : self::translate($_tmpValue, $lang);
				$template = str_replace("[#$arrContent[1]#]",$contentValue,$template);
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
		
		return _($content);
	}
	
	/**
	 * This loads a file to a String and give you the content ( its the same of PhpBURN::loadFile however to keep it independent we choose to not call directly that )
	 *
	 * @param String $filename
	 * @return String
	 */
	public static function loadFile($filename) {
		$file = file($filename);
		$content = '';
		if(file_exists($filename)) {
			foreach ($file as $key => $value) {
			   $content .= $value;
			}
		} else {
			$content = "error";
		}
		return $content;
	}
	
}
?>