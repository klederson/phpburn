<?php
require_once('config.php');
require_once('phpBurn.php');

$config = new PhpBURN_Configuration($thisConfig);

PhpBURN::import('webinsys.Teste','webinsys.subpackage.Teste2');

$teste = new Teste();

$teste->where('login','eq','teste 1');

$teste->find();

print "<hr>Memory Usage: ";
print memory_get_usage()/1024 . " Kb";

?>