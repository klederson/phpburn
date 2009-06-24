<?php
require_once('config.php');
require_once('app/phpBurn.php');

//Loading the configuration file
$config = new PhpBURN_Configuration($thisConfig);

//Importing the package file
PhpBURN::import('webinsys.Users');

//Instanciate the object
$user = new Users();

//Define some limit
$user->limit(5);
//Do the search
$user->find();

print "<pre>";
//Start to navigate into the data
while($user->fetch()) {
//	Get the ONE TO ONE Relationship
	$user->_getLink('albums');

//	A little check if user has an album or not
	if($user->albums->id_album == null) {
		print sprintf('The user <i>%s</i> has no album',$user->name);
	} else {
//		Set some clauses to MANY_TO_MANY relationships
		$user->albums->_linkWhere('tags','name LIKE("%teste%")'); //@TODO change to %test% and see the magic
		
//		Get MANY_TO_MANY relationship in Albums
		$amountPictures = $user->albums->_getLink('tags');
		
		print sprintf('The user <i>%s</i> has the <b>%s</b> album and %d pictures',$user->name, strtoupper($user->albums->name), $amountPictures);
		
		while($user->albums->tags->fetch()) {
			print "<br/>--";
			print "Picture Name: " . $user->albums->tags->name;
			print "--";
		}		
	}
	print "<br/><br/>";
}
print "</pre>";

print "<hr>Memory Usage: ";
print memory_get_usage()/1024 . " Kb";

?>