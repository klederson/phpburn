<?php
require_once('app/phpBurn.php');
require_once('config.php');

//Turn the Messages and Logs and Erros ON
PhpBURN_Message::setMode(PhpBURN_Message::FIREBUG); //You can Choose FIREPHP, BROWSER OR FILE for now than more can came latter

//Loading the configuration file
$config = new PhpBURN_Configuration($thisConfig);

//Importing the package file
PhpBURN::import('webinsys.Users');

$user = new Users();


$user->find();

while($user->fetch()) {
	print "user_id => " . $user->id . " <br/>";
	PhpBURN_Message::output("user_id=> ". $user->id);
}


print "<hr>Memory Usage: ";
print memory_get_usage()/1024 . " Kb";

?>