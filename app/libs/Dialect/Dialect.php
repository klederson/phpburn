<?php
abstract class PhpBURN_Dialect  implements IDialect  {
	
	protected $obj = null;
	protected $resultSet;
	protected $dataSet;
	protected $pointer;
		
	function __construct(PhpBurn_Core $obj) {
		$this->modelObj = &$obj;
	}
	
	function __destruct() {
		unset($this);
	}
	
	/**
	 * Prepares and returns a dataset of resuts from the database
	 * 
	 * @param $pk
	 * @return Integer $num_rows
	 */
	public function find($pk = null) {
		//Prepare the SELECT SQL Query
		$sql = $this->prepareSelect();
		
		//Clear actual dataSet
		$this->clearDataSet();
		
		//Executing the SQL
		$this->execute($sql);
		
		//Set cursor at the first position
		$this->setPointer(0);
		
		//Prepare DataSet
		//$this->setDataSet($dataSet);
		
		//Returns the amount result
		return $this->getModel()->getConnection()->affected_rows($this->resultSet);
	}
	
	public function fetch() {
		$data = is_array($this->dataSet[$this->getPointer()]) ? $this->dataSet[$this->getPointer()] : $this->getModel()->getConnection()->fetch($this->resultSet);
		if($data != null && count($data) > 0 && !is_array($this->dataSet[$this->getPointer()])) {
			$this->dataSet[$this->getPointer()] = $data;
		}
				
		if($this->moveNext() === false) {
			return false;
		} else {
			return $data;
		}
	}
	
	/**
	 * Prepares the SQL Query for SELECT complete with Joins and all needed for a SELECT.
	 * 
	 * @author Kléderson Bueno <klederson@klederson.com>
	 * 
	 * @return String $sql
	 */
	protected function prepareSelect() {		
		//Creating the selectable fields
		if(count($this->getModel()->_select) <= 0) {
			//Selecting from the map
			foreach($this->getModel()->getMap()->fields as $index => $value) {
				//Parsing non-relationship fields
				if(!$value['isRelationship'] && $value['field']['column'] != null) {
					$fields .= $fields == null ? "" : ", ";
					$fields .= sprintf("%s.%s AS %s", $this->getModel()->_tablename,$value['field']['column'], $index);
				}
			}
		} elseif(count($this->getModel()->_select) > 0) {
			//Select based ONLY in the $obj->select(); method
			foreach($this->getModel()->_select as $index => $value) {
				$fields .= $fields == null ? "" : ", ";
				$fields .= sprintf("%s AS %s", $value['value'], $value['alias']);
			}
		} else {
			$model = get_class($this->modelObj);
//			@TODO Insert here an exeption message: "[!$model is not an mapped or valid PhpBURN Model!]"			
			print "[!$model is not an mapped or valid PhpBURN Model!]";
			exit;
		}
		
		//Defnine FROM tables
		$from = 'FROM ' . $this->getModel()->_tablename;
		
		if(count($this->getModel()->_join) > 0) {
			foreach($this->getModel()->_join as $index => $value) {
				$joinString .= $joinString != null ? ' ' : null;
				$joinString .= sprintf('%s %s', $value['type'], $index);
				if($value['fieldLeft']  != null && $value['fieldRight']  != null) {
					$joinString .= sprintf(" ON '%s' %s '%s'", addslashes($value['fieldLeft']), $value['operator'], addslashes($value['fieldRight']));
				}
			}			
		}
				
		if(count($this->getModel()->_where) > 0) {
			//Define conditions
			$conditions = 'WHERE ';
			foreach($this->getModel()->_where as $index => $value) {
				//Checking swhere and where
				if(!is_array($value)) {
					//Normal where
					$whereConditions .= addslashes($value);
				} else {
					//SuperWhere
					$whereConditions .= $whereConditions == null ? "" : $value['condition'];
					$whereConditions .= sprintf("%s %s '%s'",$value['start'],$value['operator'],addslashes($value['end']));
				}
			}
		}
		
		if(count($this->getModel()->_orderBy) > 0) {
			//Define OrderBY
			$orderBy = 'ORDER BY ';
			foreach($this->getModel()->_orderBy as $index => $value) {
				$orderConditions .= $orderConditions == null ? "" : ", " . $value['type'];
				$orderConditions .= $value['field'];
			}
		}
		
		if($this->getModel()->_limit != null) {
			//Define Limit
			$limits = explode(',',$this->getModel()->_limit);
			$limit = $this->setLimit($limits[0],$limits[1]);
		}
		
		//Construct SQL
		$sql = ('SELECT ' . $fields . ' ' . $from . ' ' . $joinString . ' ' . $conditions . ' ' . $whereConditions . ' ' . $orderBy . ' ' . $orderConditions . ' ' . $limit);
		
		return $sql;
	}
	
	/* Execution */
	
	/**
	 * Calls the Connection Object and perform a SQL QUERY into the Database
	 * 
	 * @param String $sql
	 */
	public function execute($sql) {
		$this->resultSet = &$this->getModel()->getConnection()->executeSQL($sql);
	}
	
	public function save() {
		$isInsert = true;
		
		//Verify if the PK value has been set
		$pkField = $this->getMap()->getPrimaryKey();
		if(isset($this->getModel()->$pkField['field']['alias']) && !empty($this->getModel()->$pkField['field']['alias']) ) {
			$isInsert = false;
		}
		
		//Preparing the SQL
		$sql = $isInsert == true ? $this->prepareInsert() : $this->prepareUpdate();
		
		$this->execute($sql);
	}
	
	public function prepareInsert() {
		foreach ($this->getMap()->fields as $field => $infos) {
			if($infos['isRelationship'] != true) {
				$this->getMap()->setFieldValue($field, $this->getModel()->$field);
				$insertFields .= $insertFields == null ? '' : ', ';
				$insertFields .= $field;
				$value = $this->getMap()->getFieldValue($field) == '' ? 'NULL' : $this->getMap()->getFieldValue($field);
				$insertValues .= $insertValues == null ? '' : ', ';
				$insertValues .= sprintf("'%s'", $value);
			}
		}
		
		//Pre-defined parms
		$tableName = &$this->getModel()->_tablename;
		
		//Constructing the SQL
		return $sql = sprintf("INSERT INTO %s ( %s ) VALUES ( %s ) ", $tableName, $insertFields, $insertValues);
	}
	
	public function prepareUpdate() {
		$updatedFields = null;
		//Checking each MAPPED field looking in cache for changes in field value, if existis it will be updated, if not we just update the right fields
		foreach ($this->getMap()->fields as $field => $infos) {
			if($this->getModel()->$field != $infos['#value']) {
				$this->getMap()->setFieldValue($field, $this->getModel()->$field);
				$updatedFields .= $updatedFields == null ? '' : ', ';
				$updatedFields .= sprintf("%s='%s'", $field, addslashes($this->getModel()->$field));
			}
		}
		
		//Pre-defined parms
		$tableName = &$this->getModel()->_tablename;
		
		//To see more about pkField Structure see addField at MapObject
		$pkField = &$this->getMap()->getPrimaryKey();
		
		//Constructing the SQL
		$sql = $updatedFields != null ? sprintf("UPDATE %s SET %s WHERE %s='%s'", $tableName, $updatedFields, $pkField['field']['column'], $pkField['#value']) : null;
		
		if($sql == null) {
			//TODO Send an Warning Message: "[!Warning!] : [!There is nothing to save in $modelName model.!]"
			print "[!Warning!] : [!There is nothing to save in $modelName model.!]";
		} else {
			return $sql;
		}
		//$this->execute($sql);
	}
	
	public function delete() {
		
	}
	
	/* Treatment */
	
	/* Internals */
	
	/**
	 * (non-PHPdoc)
	 * @see app/libs/Dialect/IDialect#setConnection()
	 */
	public function setConnection($connection) {
		$this->connection = &$connection;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see app/libs/Dialect/IDialect#getConnection()
	 */
	public function getConnection() {
		return $this->getModel()->getConnection();
	}
	
	public function getMap() {
		return $this->getModel()->getMap();
	}
	
	public function getModel() {
		return $this->modelObj;
	}
	
	
	/* DataSet access */
	
	public function setDataSet(array $dataSet) {
		$this->dataSet = $dataSet;
	}
	
	public function getDataSet() {
		return $this->dataSet;
	}
	
	public function clearDataSet() {
		unset($this->dataSet);
	}
	
	
	/* Navigational Methods */
	
	/**
	 * (non-PHPdoc)
	 * @see app/libs/Dialect/IDialect#moveNext()
	 */
	public function moveNext() {
		if($this->pointer <= $this->getLast()) {
			$this->pointer++;
		} else {
			return false;
		}
	}
	
	/**
	 * (non-PHPdoc)
	 * @see app/libs/Dialect/IDialect#movePrev()
	 */
	public function movePrev() {
	if($this->pointer > 0) {
			$this->pointer--;
		} else {
			return false;
		}
	}
	
	/**
	 * (non-PHPdoc)
	 * @see app/libs/Dialect/IDialect#moveFirst()
	 */
	public function moveFirst() {
		$this->pointer = 0;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see app/libs/Dialect/IDialect#moveLast()
	 */
	public function moveLast() {
		$this->pointer = $this->getConnection()->num_rows($this->resultSet) - 1;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see app/libs/Dialect/IDialect#getLast()
	 */
	public function getLast() {
		return $this->getConnection()->num_rows($this->resultSet) - 1;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see app/libs/Dialect/IDialect#getPointer()
	 */
	public function getPointer() {
		return $this->pointer;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see app/libs/Dialect/IDialect#setPointer()
	 */
	public function setPointer($pointer) {
		$this->pointer = $pointer;
	}
	
}
?>