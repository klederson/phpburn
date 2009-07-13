<?php
abstract class PhpBURN_Dialect  implements IDialect  {
	
	protected $obj = null;
	public $resultSet;
	public $dataSet;
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
		$sql = $this->prepareSelect($pk);
		
		//Clear actual dataSet
		$this->clearDataSet();
		
		$modelName = get_class($this->getModel());
		
		if($sql != null) {
			$this->execute($sql);
		} else {
			PhpBURN_Message::output("[!No query found!] - <b>$modelName</b>");
			return false;
		}
		
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
	
	public function prepareDelete($pk) {
		//Defnine FROM tables
		$from = 'FROM ' . $this->getModel()->_tablename;
		
		$whereConditions = null;
			
		$pkField = $this->getModel()->getMap()->getPrimaryKey();
		$pk = $pk == null ? $this->getModel()->getMap()->getFieldValue($pkField['field']['alias']) : $pk;
		
		if(isset($pk) && !empty($pk) && $pk != null) {
			$whereConditions = sprintf("WHERE %s='%s'",$pkField['field']['column'],$pk);
		}
			
		return $sql = $whereConditions == null ? null : sprintf("DELETE %s %s", $from, $whereConditions);
	}
	
	/**
	 * Prepares the SQL Query for SELECT complete with Joins and all needed for a SELECT.
	 * 
	 * @author Kléderson Bueno <klederson@klederson.com>
	 * 
	 * @return String $sql
	 */
	public function prepareSelect($pk = null) {		
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
			PhpBURN_Message::output("$model [!is not an mapped or valid PhpBURN Model!]",PhpBURN_Message::ERROR);
			exit;
		}
		
		//Defnine FROM tables
		$from = 'FROM ' . $this->getModel()->_tablename;
		
		if(count($this->getModel()->_join) > 0) {
			foreach($this->getModel()->_join as $index => $value) {
				$joinString .= $joinString != null ? ' ' : null;
				$joinString .= sprintf('%s %s', $value['type'], $index);
				if($value['fieldLeft']  != null && $value['fieldRight']  != null) {
					$joinString .= sprintf(" ON `%s`.`%s` %s `%s`.`%s`", $this->getModel()->_tablename, ($value['fieldLeft']), $value['operator'], $index,($value['fieldRight']));
				}
			}			
		}
				
		if(count($this->getModel()->_where) > 0) {
			//Define conditions
			
			foreach($this->getModel()->_where as $index => $value) {
				//Checking swhere and where
				if(!is_array($value)) {
					//Normal where
					$whereConditions .= ($value);
				} else {
					//SuperWhere
					$whereConditions .= $whereConditions == null ? "" : sprintf(" %s ",$value['condition']);
					$whereConditions .= sprintf(" %s %s '%s' ",$value['start'],$value['operator'],($value['end']));
				}
			}
			
			if($whereConditions != null && isset($whereConditions) && !empty($whereConditions)) {
        		$conditions = 'WHERE ';
			}
		} else {
			foreach ($this->getModel()->getMap()->fields as $field => $infos) {
				if($this->getModel()->getMap()->getRelationShip($field) != true) {
					$value = $this->getModel()->getMap()->getFieldValue($field);
					if(isset($value) && !empty($value) && $value != null && $value != '') {
						$fieldInfo = $this->getModel()->getMap()->getField($field);
						$whereConditions .= $whereConditions == null ? sprintf(" %s %s '%s' ",$fieldInfo['field']['column'],'=',$value) : sprintf(" AND %s %s '%s' ",$fieldInfo['field']['column'],'=',$value);
					}
					unset($value);
				}
			}
			
			if($whereConditions != null && isset($whereConditions) && !empty($whereConditions)) {
        		$conditions = 'WHERE ';
			}
		}
		
		if($pk != null) {
				$pkField = $this->getModel()->getMap()->getPrimaryKey();
				$whereConditions .= $whereConditions == null ? sprintf("WHERE %s='%s' ",$pkField['field']['alias'],$pk) : sprintf(" AND %s='%s' ",$pkField['field']['alias'],($pk));
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
		PhpBURN_Message::output("[!Performing the query!]: $sql");
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
		
		if($sql != null) {
			$this->execute($sql);
			$this->getModel()->get($this->getModel()->getConnection()->last_id());
		} else {
			return false;
		}
	}
	
	public function prepareInsert() {
		foreach ($this->getModel()->getMap()->fields as $field => $infos) {
			if($this->getModel()->getMap()->getRelationShip($field) != true) {
				$this->getModel()->getMap()->setFieldValue($field, $this->getModel()->$field);
				$value = $this->getModel()->getMap()->getFieldValue($field);
				if(isset($value) && $value != null) {
					$insertFields .= $insertFields == null ? '' : ', ';
					$insertFields .= $infos['field']['column'];
					$insertValues .= $insertValues == null ? '' : ', ';
					$insertValues .= sprintf("'%s'", $value);
				}
			} else if($this->getModel()->getMap()->getRelationShip($field) == true && !empty($this->getModel()->$field)) {
				//print $field . "<br/>";
				$this->getModel()->$field->save();
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
			if($this->getModel()->$field != $infos['#value'] && $this->getModel()->getMap()->getRelationShip($field) != true) {
				$this->getMap()->setFieldValue($field, $this->getModel()->$field);
				$updatedFields .= $updatedFields == null ? '' : ', ';
				$updatedFields .= sprintf("%s='%s'", $infos['field']['column'], ($this->getModel()->$field));
			} else if($this->getModel()->getMap()->getRelationShip($field) == true && !empty($this->getModel()->$field)) {
				//print $field . "<br/>";
				$this->getModel()->$field->save();
			}
		}
		
		//Pre-defined parms
		$tableName = &$this->getModel()->_tablename;
		
		//To see more about pkField Structure see addField at MapObject
		$pkField = &$this->getMap()->getPrimaryKey();
		
		//Constructing the SQL
		$sql = $updatedFields != null ? sprintf("UPDATE %s SET %s WHERE %s='%s'", $tableName, $updatedFields, $pkField['field']['column'], $pkField['#value']) : null;
		
		$modelName = get_class($this->getModel());
		if($sql == null) {
			PhpBURN_Message::output("[!There is nothing to save in model!]: <b>$modelName</b>",PhpBURN_Message::WARNING);
		} else {
			return $sql;
		}
		//$this->execute($sql);
	}
	
	public function delete($pk = null) {
//		Getting the DELETE QUERY
		$sql = $this->prepareDelete($pk);
		
		if($sql != null) {
			$this->execute($sql);
		} else {
			$modelName = get_class($this->getModel());
			PhpBURN_Message::output("[!Nothing to delete!] - <b>$modelName</b>");
			return false;
		}
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