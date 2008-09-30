<?php
interface IDialect
{

	function __construct(PhpBurn_Core $obj);
	public function setConnection($cnn);
	public function getConnection();
	public function execute($sql);
	public function num_rows();
	public function affected_rows();
	public function moveNext();
	public function movePrev();
	public function moveFirst();
	public function moveLast();
	public function fetch_row($rowNumber);
	public function fetch();
	public function getErrorMsg();
	
	public function getDataset();
	public function setDataset(array $dataset);
	
	public function getPointer();
	public function setPointer($pointer);

}
?>