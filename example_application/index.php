<?php
require_once('config.php');
require_once('phpBurn.php');

$config = new PhpBURN_Configuration($thisConfig);

PhpBURN::import('webinsys.Teste','webinsys.subpackage.Teste2');

//$teste = new Teste();

$teste2 = new Teste();
$teste = new Teste2();

$teste2->_mapObj->setFieldValue('login','ae <br/>');

print $teste2->login;

$teste->_mapObj->setFieldValue('name','<h1 style="cursor: default">bla bla bla</h1>');

print $teste->name;


//$teste->getMap();
//$teste2->getMap();
print "<hr>Memory Usage: ";
print memory_get_usage()/1024 . " Kb";

?>