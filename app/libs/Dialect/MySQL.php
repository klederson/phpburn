<?php
PhpBurn::load("Dialect.IDialect");

class PhpBURN_Dialect_MySQL implements IDialect {
	
	private $obj = null;
	private $connection = null;
	private $resultSet;
	private $dataSet;
	private $mode;
	private $pointer;
	
	function __construct(PhpBurn_Core $obj) {
		$this->obj =& $obj;
	}
	function __destruct() {
		unset($this);
	}
	
	/* Query Construction */
	
	public function find($pk = null) {
		
	}
	
	protected function _prepareSQL($forCount = false, $what = '*') {
		$sql = "SELECT %s";

		return sprintf($sql, ($forCount?'COUNT('.$what.')':$what));
	}
	
	public function setConnection($connection) {
		$this->connection =& $connection;
	}
	
	public function getConnection() {
		return $this->connection;
	}
	
	public function setMysqlMode($mode = "") {
		$this->mode = $mode;
		
		if (empty($mode))
			$this->setMysqlMode(MYSQL_ASSOC);
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
	
	public function moveFirst() {
		$this->pointer = 0;
	}
	
	public function moveNext() {
		$this->pointer++;
	}
	
	public function movePrev() {
		$this->pointer--;
	}
	
	public function moveLast() {
		$this->pointer = $this->num_rows() - 1;
	}
	
	public function getLast() {
		return $this->num_rows() - 1;
	}
	
	public function fetch_row($rowNumber) {
		if (!isset($this->dataSet) && !isset($this->dataSet[$rowNumber]))
			return false;
		return $this->dataSet[$rowNumber];
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
	
//	@TODO: Create this method
	public function getErrorMsg() {
		return $this->getConnection()->getErrorMsg();
	}
	
	public function getDataSet() {
		return $this->dataSet;
	}
	
	public function setDataSet(array $dataSet) {
		$this->dataSet = $dataSet;
	}
	
	public function getPointer() {
		return $this->pointer;
	}
	
	public function setPointer($pointer) {
		$this->pointer = $pointer;
	}
}
?>
