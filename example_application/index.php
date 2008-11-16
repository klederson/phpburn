<?php
require_once('config.php');
require_once('phpBurn.php');

$config = new PhpBURN_Configuration($thisConfig);

PhpBURN::import('webinsys.Teste','webinsys.subpackage.Teste2');

//$teste = new Teste();

$teste2 = new Teste();
$teste3 = new Teste();
$teste = new Teste2();

//Testando função interna de field
$teste2->_mapObj->setFieldValue('login','ae <br/>');

if(isset($teste2->login)) {
	print "ISSET: " . $teste2->login;
}

$teste->_mapObj->setFieldValue('name','<h1 style="cursor: default">My H1</h1>');

print "Model Attribute with html in content: " . $teste->name;


//$teste->getMap();
//$teste2->getMap();
print "<hr>Memory Usage: ";
print memory_get_usage()/1024 . " Kb";

?>