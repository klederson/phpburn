<?php
//Required Routes
$routes['__defaultAction'] = 'index';
$routes['__defaultController'] = 'main';

//Route example (you can also see more at Router documentation)
$routes['album'] = "albumController";
$routes['album/([a-zA-Z]+)'] = "albumController/$1";
$routes['album/([a-zA-Z]+)/([a-zA-Z0-9 ]+)'] = "albumController/$1/$2";
$routes['album/([a-zA-Z]+)/([a-zA-Z0-9 ]+)/([a-zA-Z0-9 ]+)'] = "albumController/$1/$2/$3";
$routes['album/([a-zA-Z]+)/([a-zA-Z0-9 ]+)/([a-zA-Z0-9 ]+)/([a-zA-Z0-9 ]+)'] = "albumController/$1/$2/$4";
?>