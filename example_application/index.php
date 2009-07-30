<?php
require_once('app/phpBurn.php');
require_once('config.php');

//Turn the Messages and Logs and Erros ON
PhpBURN_Message::setMode(PhpBURN_Message::FIREBUG); //You can Choose FIREPHP, BROWSER OR FILE for now than more can came latter

//Loading the configuration file
$config = new PhpBURN_Configuration($thisConfig);

PhpBURN::load('Extras.Util.Router');

$route['album'] = "albumsController";
$route['album/([a-zA-Z]+)'] = "albumsController/$1";
$route['album/([a-zA-Z]+)/([a-zA-Z0-9 ]+)'] = "albumsController/$1/$2";

$router = new Router($route);
$router->parseRoutes();
?>