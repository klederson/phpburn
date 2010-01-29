<?php
################################
# Hooks
################################
define('SYS_USE_FIREPHP',true,true);

################################
# Including required files
################################
require_once('app/phpBurn.php');
require_once('config.php');

//Migrations tool
PhpBURN::load('Migrations.Migrations');

################################
# Starting application
################################
PhpBURN_Migrations::migrate();
?>