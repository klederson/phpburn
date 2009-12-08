<?php
define('SYS_USE_FIREPHP',true);
require_once('app/phpBurn.php');
require_once('config.php');
PhpBURN::load('Migrations.Migrations');

PhpBURN_Migrations::migrate();
?>