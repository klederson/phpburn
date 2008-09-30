<?php
class testinho extends parentClasse {
	public $teste;
	public $dois;
	
	public function testes() {
		$this->oi();
	}
}

abstract class parentClasse {
	
	public function oi() {
		$this->teste = "oi";
	}
	
}

$teste = new testinho();
$teste->testes();
//print $teste->teste;

$arrayTeste['nome'] = array(
	'teste' => array('column' => 'rara','column2' => 'aaaadddd')
);
$arrayTeste['nome2'] = array(
	'teste' => array('column' => 'rara1')
);
$arrayTeste['nome3'] = array(
	'teste' => array('column' => 'rara2')
);
$arrayTeste['nome4'] = array(
	'teste' => array('column' => 'rara3')
);

if(array_walk_recursive($arrayTeste,'checkColumns','raraz') == true) {
	print "oi";
}

function checkColumns($value,$index,$columnName) {
	if($index == 'column' && $value == $columnName) {
		print $value;
		return false;
	}
}
?>