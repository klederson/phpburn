<?php
interface IPhpBurn {
	public function get();
	
	public function find();
	
	public function fetch();
	
	public function save();
	
	public function delete();
	
	public function order();
	
	public function limit($offset = null, $limit = null);

}
?>