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

$user->_moveNext();
print "_moveNext() => " . $user->id . " <br/>";

$user->_moveNext();
print "_moveNext() => " . $user->id . " <br/>";

$user->_moveNext();
print "_moveNext() => " . $user->id . " <br/>";

$user->_moveNext();
print "_moveNext() => " . $user->id . " <br/>";

$user->_moveNext();
print "_moveNext() => " . $user->id . " <br/>";

$user->_moveTo(2);
print "_moveTo() => " . $user->id . " <br/>";

$user->_movePrev();
print "_movePrev() => " . $user->id . " <br/>";

$user->_moveTo(0);
print "_moveTo() => " . $user->id . " <br/>";

$user->_movePrev();
print "_movePrev() => " . $user->id . " <br/>";

PhpBURN_Message::output($user->getDialect()->dataSet);

/*

$user->_moveNext();
print "_moveNext() => " . $user->id . " <br/>";
PhpBURN_Message::output("_moveNext() => " . $user->id);

$user->_movePrev();
print "_movePrev() => " . $user->id . " <br/>";
PhpBURN_Message::output("_movePrev() => " . $user->id);

$user->_moveLast();
print "_moveLast() => " . $user->id . " <br/>";
PhpBURN_Message::output("_moveLast() => " . $user->id);

$user->_moveFirst();
print "_moveFirst() => " . $user->id . " <br/>";
PhpBURN_Message::output("_moveFirst() => " . $user->id);

$user->_moveTo(3);
print "_moveTo() => " . $user->id . " <br/>";
PhpBURN_Message::output("_moveTo() => " . $user->id);
*/

print "<hr>Memory Usage: ";
print memory_get_usage()/1024 . " Kb";

?>