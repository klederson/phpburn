<?php
require_once('config.php');
require_once('app/phpBurn.php');

$config = new PhpBURN_Configuration($thisConfig);

PhpBURN::import('webinsys.subpackage.Users3');

$teste = new Users();

//$teste->swhere('login','=','teste 1');
//$teste->where('id=1');
$teste->limit(0,1);
$teste->find();
//teste->fetch();

//$teste->save();


//$teste->join('albums');
//$teste->join('teste','users.id','teste.id_user');
//$teste->find(1);


while($teste->fetch()) {
	print "<br/><pre>";
//	print_r($teste);
	$teste->_linkWhere('albums','1');
	$teste->_getLink('albums');
	// $teste->albums->user_id;
	print "</pre>";
}
/*
PhpBURN::import('webinsys.Albums');
$teste->login = "Acid";
$teste->albums = new Albums();
$teste->albums->user_id = 3;

$teste->save();
*/

print "<hr>Memory Usage: ";
print memory_get_usage()/1024 . " Kb";

?>