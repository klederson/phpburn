<?php
PhpBURN::load('Dialect.Dialect_Operations');
PhpBURN::load("Dialect.IDialect");


class PhpBURN_Dialect_MySQL extends PhpBURN_Dialect_Operations implements IDialect  {
	
	protected $obj = null;
	private $connection = null;
	private $connObj = null;
	private $resultSet;
	private $dataSet;
	private $mode;
	private $pointer;
		
	function __construct(PhpBurn_Core $obj) {
		$this->obj = &$obj;
		$this->connObj = &$obj->_connObj;		
	}
	function __destruct() {
		unset($this);
	}
	
	/* Common Persistent Methods */
	
	public function find($pk = null) {
		//Start checking the model object
		print $sql = $this->prepareSelect();
		
		$this->execute($sql);
		
	}

	public function save() {
		
	}
	
	public function delete() {
		
	}
	
	public function num_rows() {
		if (!isset($this->resultSet) && empty($this->resultSet))
			return 0;
		return $this->getConnection()->num_rows($this->resultSet);
	}
	
	public function affected_rows() {
		if (!isset($this->resultSet) && empty($this->resultSet))
			return false;
		return $this->getConnection()->affected_rows();
	}
	
	public function fetch() {
		$point = $this->getPointer();
		$this->moveNext();
		if ($point > $this->getLast()) {
			$this->moveFirst();
			return false;
		}
		return $this->fetch_row($point);
	}
	
	public function fetch_row($rowNumber) {
		if (!isset($this->dataSet) && !isset($this->dataSet[$rowNumber]))
			return false;
		return $this->dataSet[$rowNumber];
	}
	
	/* Functional Methods */
	
	public function setConnection($connection) {
		$this->connection = &$connection;
	}
	
	public function getConnection() {
		return $this->connection;
	}
	
	public function setMysqlMode($mode = "") {
		$this->mode = $mode;
		
		if (empty($mode))
			$this->setMysqlMode(MYSQL_ASSOC);
	}
	
	public function setDataSet(array $dataSet) {
		$this->dataSet = $dataSet;
	}
	
	public function getDataSet() {
		return $this->dataSet;
	}
	
	public function execute($sql, $mode = "") {
		if (!isset($this->connection) && empty($this->connection))
			return false;
		$this->setMysqlMode($mode);
		$this->resultSet = $this->connection->executeSQL($sql);
		while ($row = mysql_fetch_array($this->resultSet, $this->mode)) {
			$dataSet[] = $row;
		}
		$this->setDataSet($dataSet);
		$this->setPointer(0);
		return true;
	}
	
	public function resultToObject(array $resultSet) {
		
	}
	
	/* Auxiliar Methods */
	
	protected function handleSelect() {
		
	}
	
	public function callStoredProcedure($name,$attributes = array() ) {
		
	}

	/* Navigational Methods */
	
	public function moveNext() {
		$this->pointer++;
	}
	
	public function movePrev() {
		$this->pointer--;
	}
	
	public function moveFirst() {
		$this->pointer = 0;
	}
	
	public function moveLast() {
		$this->pointer = $this->num_rows() - 1;
	}
	
	public function getLast() {
		return $this->num_rows() - 1;
	}
	
	public function getPointer() {
		return $this->pointer;
	}
	
	public function setPointer($pointer) {
		$this->pointer = $pointer;
	}
	
	/* Database methods */
	public function _getSelectQuery() {
		
	}
	
	public function _getUpdateQuery() {
		
	}
	
	public function _getInsertQuery() {
		
	}
	
	public function _getDeleteQuery() {
		
	}
	
	public function _getMultiInsertQuery() {
		
	}
	
	
	/* Other Methods */
	
//	@TODO: Create this method
	public function getErrorMsg() {
	}
}
?>