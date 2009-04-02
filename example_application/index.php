<?php
require_once('config.php');
require_once('app/phpBurn.php');

$config = new PhpBURN_Configuration($thisConfig);

PhpBURN::import('webinsys.Users');

$teste = new Users();

$teste->swhere('login','=','teste 1');

$teste->join('albums');
$teste->join('teste','users.id','teste.id_user');

$teste->find();

print "<hr>Memory Usage: ";
print memory_get_usage()/1024 . " Kb";

?>