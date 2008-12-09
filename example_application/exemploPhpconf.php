<?php
PhpBURN::import('webinsys.Users');

$user = new Usuarios();
$user->get(1); //busca o usuário de PK 1
print $user->name; //imprime o nome do usuário


PhpBURN::import('webinsys.Users');

$user = new Usuarios();
$user->get(1); //busca o usuário de PK 1
$user->pass = "novasenha";
$user->save(); //salva apenas o campo que foi modificado

?>