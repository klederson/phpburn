<?php
/**
 * This file has been designed to auto-config all your
 * path variables like path, url and others automaticaly
 * for this it have to stay the same folder as your index.php
 * 
 * If you want to put it in another place you should to config manualy
 * or modify our auto-generate code.
 * 
 * @author Kléderson Bueno <klederson@klederson.com>
 * @version 1.0
 */
ob_start();

//Error Reporting
error_reporting(E_ALL & ~E_NOTICE);
//error_reporting(E_ALL);

//Setup locale for lots of internal reasons ( it will be good for you just make sure you use internacionalization correctly )
setlocale(LC_ALL, 'pt_BR');

//Call the main system configurations
require_once('sysConfig.php');

//Call the main system configurations
require_once('config/database.php');
?>
