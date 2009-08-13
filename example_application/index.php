<?php
require_once('app/phpBurn.php');
require_once('config.php');

//Loading the configuration file
$config = new PhpBURN_Configuration($thisConfig);

//Turn the Messages and Logs and Erros ON
PhpBURN_Message::setMode(PhpBURN_Message::FIREBUG); //You can Choose FIREPHP, BROWSER OR FILE for now than more can came latter

//Loading Modules
PhpBURN::loadModule('Model','View','Controller');
/* All configuration such as  autoLoads, and other stuff should be putted before the startApplication() */
PhpBURN::startApplication();

?>