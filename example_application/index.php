<?php
require_once('config.php');
require_once('app/phpBurn.php');

$config = new PhpBURN_Configuration($thisConfig);

PhpBURN::import('webinsys.subpackage.Users3');

$teste = new Users();

$teste->swhere('login','=','teste 1');
$teste->limit(0,10);

//$teste->join('albums');
//$teste->join('teste','users.id','teste.id_user');
$teste->find(1);

while($teste->fetch()) {
	print "<br/><pre>";
	//print_r($teste);
	$teste->_getLink('albums');
	print $teste->albums->user_id;
	print "</pre>";
}
$teste->save();


print "<hr>Memory Usage: ";
print memory_get_usage()/1024 . " Kb";

?>