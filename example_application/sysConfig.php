<?php
/*
 * System Configuration File
 * 
 * This file contains the main constants and variables ( as well some functions ) 
 * to make the system work all right and plug'n'play.
 */

/**
 * Base URL variable (auto-detect)
 * @var String
 */
$base_url = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on") ? "https" : "http");
$base_url .= "://".$_SERVER['HTTP_HOST'];
$base_url .= str_replace(basename($_SERVER['SCRIPT_NAME']),"",$_SERVER['SCRIPT_NAME']);

if (function_exists('realpath') AND @realpath(dirname(__FILE__)) !== FALSE)
{
	$system_folder = realpath(dirname(__FILE__)) . '/';
}

define('SYS_BASE_URL',$base_url,true);
define('SYS_BASE_PATH',$system_folder,true);
define('SYS_CSS_PATH',$system_folder . '/public/css/',true);
define('SYS_IMAGE_PATH',$system_folder . '/public/images/',true);
define('SYS_JAVASCRIPT_PATH',$system_folder . '/public/js/',true);
define('SYS_FILE_PATH',$system_folder . '/public/files/',true);

/**
 * Extra Libs
 */
define('SYS_USE_FIREPHP',true,true);
define('SYS_USE_DATEFORMAT',"%a, %b : %H:%M:%S",true); //To see more read about srftime
?>