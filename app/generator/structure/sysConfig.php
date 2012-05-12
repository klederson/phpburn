<?php
/*
 * System Configuration File
 *
 * This file contains the main constants and variables ( as well some functions )
 * to make the system work all right and plug'n'play.
 */


##########################################
# URL
##########################################
/**
 * Base URL variable (auto-detect)
 * @var String
 */
@$baseUrl = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on") ? "https" : "http");
@$baseUrl .= "://".$_SERVER['HTTP_HOST'];
@$baseUrl .= str_replace(basename($_SERVER['SCRIPT_NAME']),"",$_SERVER['SCRIPT_NAME']);

if (function_exists('realpath') AND @realpath(dirname(__FILE__)) !== FALSE)
{
	$basePath = realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR;
}

define('SYS_BASE_URL',$baseUrl,true);

##########################################
# PATH
##########################################
define('DS',DIRECTORY_SEPARATOR,true);
define('SYS_BASE_PATH',$basePath,true);
define('SYS_APPLICATION_PATH', SYS_BASE_PATH . DS . 'system');
define('SYS_MODEL_PATH',SYS_APPLICATION_PATH . DS . 'model' . DS,true);
define('SYS_VIEW_PATH',SYS_APPLICATION_PATH . DS . 'views' . DS,true);
define('SYS_CONTROLLER_PATH',SYS_APPLICATION_PATH . DS . 'controllers' . DS,true);
define('SYS_SPICES_PATH', SYS_APPLICATION_PATH . DS . 'config' . DS . 'spices' . DS);

define('SYS_CSS_PATH',SYS_BASE_PATH . DS . 'public' . DS . 'css' ,true);
define('SYS_IMAGE_PATH',SYS_BASE_PATH . DS . 'public' . DS . 'images',true);
define('SYS_JAVASCRIPT_PATH',SYS_BASE_PATH . DS . 'public' . DS . 'js',true);
define('SYS_FILE_PATH',SYS_BASE_PATH . DS . 'public' . DS . 'files',true);


##########################################
# EXTENSIONS
##########################################
define('SYS_MODEL_EXT', '.php',true);
define('SYS_VIEW_EXT', 'php',true);
define('SYS_CONTROLLER_EXT', 'php',true);
define('SYS_SPICES_EXT','spices.php',true);


################################
# Infos
################################
define('PHPBURN_SESSIONNAME', md5(SYS_BASE_PATH), true);
define('PHPBURN_VERSION','0.9b',true);
define('PHPBURN_WEBSITE','http://www.phpburn.com/',true);
define('PHPBURN_REPOSITORY_TYPE','git',true);
define('PHPBURN_REPOSITORY','http://github.com/klederson/phpburn/tree/master',true);


##########################################
# EXTRAS
##########################################
define('SYS_USE_DATEFORMAT',"%a, %b : %H:%M:%S",true); //To see more read about srftime
?>