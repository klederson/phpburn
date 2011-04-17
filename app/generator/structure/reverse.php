<?php
################################
# Hooks
################################
define('SYS_USE_FIREPHP',false,true);

################################
# Including required files
################################
require_once('app/phpBurn.php');
require_once('config.php');

################################
# Start PhpBURN needed resources
################################
PhpBURN::enableAutoload();
PhpBURN_Message::setMode(PhpBURN_Message::CONSOLE);

//Migrations tool
PhpBURN::load('Migrations.Reverse');

################################
# Starting application
################################
PhpBURN_Reverse::init();
?>