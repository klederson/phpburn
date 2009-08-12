<?php
require_once('config.php');
require_once('app/phpBurn.php');

$config = new PhpBURN_Configuration($thisConfig);

PhpBURN::import('webinsys.Users','webinsys.Albums');

$user = new Users();

$user->login = 'caio';
$user->albums = new Albums;
$user->albums->user_id = '1';
$user->save();
?>