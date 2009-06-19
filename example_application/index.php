<?php
require_once('config.php');
require_once('app/phpBurn.php');

$config = new PhpBURN_Configuration($thisConfig);

PhpBURN::import('webinsys.subpackage.Users3');

$teste = new Users();

//$teste->swhere('login','=','teste 1');
$teste->limit(0,10);

//$teste->join('albums');
//$teste->join('teste','users.id','teste.id_user');

$teste->find();

while($teste->fetch()) {
	print "<br/>";
	print $teste->id;
	$teste->login = "Oi";
	print "<br/>";	
}

$teste->save();
print "<hr/>";
$teste2 = new Users();
$teste2->login = "OEE";
$teste2->nome = "fuck";
$teste2->save();

print "<hr>Memory Usage: ";
print memory_get_usage()/1024 . " Kb";

?>