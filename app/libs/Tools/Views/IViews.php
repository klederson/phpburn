<?php
interface IViews {
	/**
	 * This function must translate the tolkens in the given string.
	 * In case of translation a language must be setted
	 * 
	 * @package PhpBURN
 	 * @subpackage Views
	 *
	 * @param String $template
	 * @param Array $tolkens
	 * @param String $lang
	 */
	public static function translateTolkens($template, $tolkens, $lang = null);
	
	/**
	 * This loads a file to a String and give you the content ( its the same of PhpBURN::loadFile however to keep it independent we choose to not call directly that )
	 *
	 * @param String $filename
	 * @return String
	 */
	public static function loadFile($filename);
}
?>