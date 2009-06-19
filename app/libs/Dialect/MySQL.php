<?php
PhpBURN::load('Dialect.Dialect');
PhpBURN::load("Dialect.IDialect");

/**
 * This class manages all content, queries and result stuff in a system level and for all database driver interaction level
 * it calls PhpBURN_Connection object that is the main responsable between the application data and database interaction
 * 
 * @author Klederson Bueno <klederson@klederson.com>
 *
 */
class PhpBURN_Dialect_MySQL extends PhpBURN_Dialect  implements IDialect {
		
	/* Common Persistent Methods */
	
	/**
	 * (non-PHPdoc)
	 * @see app/libs/Dialect/PhpBURN_Dialect#find()
	 */
	public function find($pk = null) {
		return parent::find($pk);
	}
	
	/**
	 * Distinct Fields in a query
	 * 
	 * @param $field
	 */
	public function distinct($field) {
		
	}
	
	/**
	 * Select a Field as a UnixTimestamp
	 * 
	 * @param $field
	 */
	public function unixTimestamp($field) {
		
	}
	
	/**
	 * (non-PHPdoc)
	 * @see app/libs/Dialect/IDialect#affected_rows()
	 */
	public function affected_rows() {
		if (!isset($this->resultSet) && empty($this->resultSet))
			return false;
		return $this->getConnection()->affected_rows();
	}
	
	public function fetch_row($rowNumber) {
		if (!isset($this->dataSet) && !isset($this->dataSet[$rowNumber]))
			return false;
		return $this->dataSet[$rowNumber];
	}
	
	/* Functional Methods */
		
	public function resultToObject(array $resultSet) {
		
	}
	
	public function setLimit($offset = null, $limit = null) {
		if($offset == null && $limit == null)
		{
			return;
		} else if($offset == null && $limit != null) {
			return sprintf("LIMIT %d", $limit);
		} else if($offset != null && $limit == null) {
			return sprintf("LIMIT %d", $offset);
		} else {
			return sprintf("LIMIT %d, %d", $offset, $limit);
		}
	}
	
	/* Auxiliar Methods */
	
	protected function handleSelect() {
		
	}
	
	public function callStoredProcedure($name,$attributes = array(), $alias = null) {
		$alias = $alias = null ? $name : $alias;
	}

	public function setMysqlMode($mode = "") {
		$this->getConnection()->mode = $mode;
		
		if (empty($mode))
			$this->getConnection()>mode(MYSQL_ASSOC);
	}
	
	
	/* Other Methods */
	
//	@TODO: Create this method
	public function getErrorMsg() {
	}
}
?>