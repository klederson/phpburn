<?php
interface IDialect
{

	function __construct(PhpBurn_Core $obj);
	
	/* Common Persistent Methods */
	public function find($pk = null); //Performs a search/select into the database based on parms
	public function save(); //Insert and Update
	public function delete(); //Remove the register from database
	public function num_rows();
	public function affected_rows();
	public function fetch();
	public function fetch_row($rowNumber);
	
	/* Functional Methods */
	public function setConnection($cnn);
	public function getConnection();
	public function getDataset();
	public function setDataset(array $dataset);
	public function execute($sql); //The main method
	
	/* Navigational Methods */
	public function moveNext();
	public function movePrev();
	public function moveFirst();
	public function moveLast();
	public function getLast();
	public function getPointer();
	public function setPointer($pointer);
	
	/* Database methods */
	public function _getSelectQuery();
	public function _getUpdateQuery();
	public function _getInsertQuery();
	public function _getDeleteQuery();
	public function _getMultiInsertQuery();
	
	/* Other methods */
	public function getErrorMsg();
	

}
?>