<?php
/**
 * @package PhpBURN
 * @subpackage Dialect
 * 
 * @author klederson
 *
 */
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
			$this->resultSet = &$this->getModel()->getConnection()->executeSQL($sql);
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
	
	/**
	 * (non-PHPdoc)
	 * @see app/libs/Dialect/IDialect#fetch()
	 */
	public function fetch() {
					
			if($this->getPointer() > $this->getLast() ) {
				$this->setPointer($this->getLast());
				return false;
			} else {						
				$data = $this->dataExists($this->dataSet[$this->getPointer()]) ? $this->dataSet[$this->getPointer()] : $this->getModel()->getConnection()->fetch($this->resultSet);
				
				//PhpBURN_Message::output("Pointer ".$this->getPointer());
				
				if($data != null && count($data) > 0 && !is_array($this->dataSet[$this->getPointer()])) {
					$this->dataSet[$this->getPointer()] = $data;
				}
				
				return $data;
			}
			
	}
	
	/**
	 * Verify if the data is already stored into database cache application
	 * 
	 * @param Integer $pointer
	 * @return Boolean
	 */
	public function dataExists($pointer) {
		return is_array($this->dataSet[$pointer]) ? true : false;
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
		//Globals
		$pkField = $this->getModel()->getMap()->getPrimaryKey();
		$parentFields = $this->getModel()->getMap()->getParentFields();
		$parentClass = get_parent_class($this->getModel());
		
		
		//Join the extended classes
		foreach($parentFields as $index => $value) {
			$classVars = get_class_vars($parentClass);
			if($parentClass == $value['classReference']) {
				$tableLeft = $this->getModel()->_tablename;
			} else {
				$tableLeft = $classVars['_tablename'];
			}
			
			$this->getModel()->join($value['field']['tableReference'],$value['field']['column'],$value['field']['column'],'=', 'JOIN', $tableLeft);
			unset($classVars);
		}
		
		//Creating the selectable fields
		if(count($this->getModel()->_select) <= 0) {
			//Selecting from the map
			foreach($this->getModel()->getMap()->fields as $index => $value) {
				//Parsing non-relationship fields
				if(!$value['isRelationship'] && $value['field']['column'] != null) { //&& $value['classReference'] == get_class($this->getModel())) {
					$fields .= $fields == null ? "" : ", ";
					$fields .= sprintf("%s.%s AS %s", $value['field']['tableReference'],$value['field']['column'], $index);
				}
			}
		} elseif(count($this->getModel()->_select) > 0) {
			//Select based ONLY in the $obj->select(); method
			foreach($this->getModel()->_select as $index => $value) {
				$fields .= $fields == null ? "" : ", ";
				$fields .= sprintf("%s.%s AS %s", $value['field']['tableReference'], $value['value'], $value['alias']);
			}
		} else {
			$model = get_class($this->modelObj);
			PhpBURN_Message::output("$model [!is not an mapped or valid PhpBURN Model!]",PhpBURN_Message::ERROR);
			exit;
		}

		$from = 'FROM ' . $this->getModel()->_tablename;
		
		//Define Join SENTENCE
		if(count($this->getModel()->_join) > 0) {
			$joinString = $this->getJoinString();
		}
				
		//Define Where SENTENCE
		if(count($this->getModel()->_where) > 0) {
			
			
			foreach($this->getModel()->_where as $index => $value) {
				//Checking swhere and where
				if(!is_array($value)) {
					//Normal where
					$whereConditions .= ($value);
				} else {
					//SuperWhere
					$fieldInfo = $this->getModel()->getMap()->getField($value['start']);
					$whereConditions .= $whereConditions == null ? "" : sprintf(" %s ",$value['condition']);
					$whereConditions .= sprintf(" %s.%s %s '%s' ",$fieldInfo['field']['tableReference'],$value['start'],$value['operator'],($value['end']));
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
						$whereConditions .= $whereConditions == null ? sprintf(" %s.%s %s '%s' ",$fieldInfo['field']['tableReference'],$fieldInfo['field']['column'],'=',$value) : sprintf(" AND %s.%s %s '%s' ",$fieldInfo['field']['tableReference'],$fieldInfo['field']['column'],'=',$value);
					}
					unset($value);
				}
			}
			
			if($whereConditions != null && isset($whereConditions) && !empty($whereConditions)) {
        		$conditions = 'WHERE ';
			}
		}
		
		if($pk != null) {
				$whereConditions .= $whereConditions == null ? sprintf("WHERE %s.%s='%s' ",$this->getModel()->_tablename,$pkField['field']['column'],$pk) : sprintf(" AND %s.%s='%s' ",$this->getModel()->_tablename,$pkField['field']['column'],($pk));
		}
		
		//Define OrderBY SENTENCE
		if(count($this->getModel()->_orderBy) > 0) {
			$orderConditions = $this->getOrderByString();
		}
		
		//Define Limit SENTENCE
		if($this->getModel()->_limit != null) {
			$limits = explode(',',$this->getModel()->_limit);
			$limit = $this->setLimit($limits[0],$limits[1]);
		}
		
		//Construct SQL
		$sql = ('SELECT ' . $fields . ' ' . $from . ' ' . $joinString . ' ' . $conditions . ' ' . $whereConditions . ' ' . $orderConditions . ' ' . $limit . ';');
		
		unset($fieldInfo, $fields, $from, $joinString, $conditions, $whereConditions, $orderBy, $orderConditions, $limit, $pkField, $parentFields, $parentClass);
		
		return $sql;
	}
	
	public function getJoinString() {
		
		foreach($this->getModel()->_join as $index => $value) {
			$value['tableLeft'] = $value['tableLeft'] == null ? $this->getModel()->_tablename : $value['tableLeft'];
			$joinString .= $joinString != null ? ' ' : null;
			$joinString .= sprintf('%s %s', $value['type'], $index);
			if($value['fieldLeft']  != null && $value['fieldRight']  != null) {
				$joinString .= sprintf(" ON `%s`.`%s` %s `%s`.`%s`", $value['tableLeft'], ($value['fieldLeft']), $value['operator'], $index,($value['fieldRight']));
			}
		}
		
		return $joinString;
	}
	
	
	public function getOrderByString() {
		
		$orderBy = 'ORDER BY ';
		foreach($this->getModel()->_orderBy as $index => $value) {
			$fieldInfo = $this->getModel()->getMap()->getField($value['field']);
			$orderConditions .= $orderConditions == null ? "" : ", ";
			$orderConditions .= $fieldInfo['field']['tableReference'] . '.' . $fieldInfo['field']['column'] . ' ' . $value['type'];
		}
	
		
		return $orderBy . $orderConditions;
	}
	/* Execution */
	
	/**
	 * Calls the Connection Object and perform a SQL QUERY into the Database
	 * 
	 * @param String $sql
	 */
	public function execute($sql) {
		PhpBURN_Message::output("[!Performing the query!]: $sql");
		return $this->getModel()->getConnection()->executeSQL($sql);
		//$this->resultSet = &$this->getModel()->getConnection()->executeSQL($sql);
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
		
		$sql = array_reverse($sql, true);
		
//		print "<pre>";
//		foreach($sql as $index => $value) {
//				if($isInsert == true) {
//					$parentClass = $this->getModel()->getMap()->getTableParentClass($index);
//					$lastId = $this->getModel()->getConnection()->last_id();
//					$parentField = $this->getModel()->getMap()->getClassParentField($parentClass);
//					print_r($parentField);
//					$parentColumn = $parentField['field']['column'] != null && !empty($parentField['field']['column']) ? sprintf(', %s', $parentField['field']['column']) : '';
//					$parentValue = $parentField['field']['column'] != null && !empty($parentField['field']['column']) ? sprintf(", '%s'",$lastId) : '';
//					$value = str_replace('[#__fieldLink#]', $parentColumn, $value);
//					$value = str_replace('[#__fieldLinkValue#]',$parentValue, $value);
//				}
//				//$this->execute($value);
//				print $value . '<hr/>';
//			}
//		
//		
//		print_r($sql);
//		exit;
		
		//if($sql != null) {
		if(count($sql) > 0) {
			foreach($sql as $index => $value) {
				if($isInsert == true) {
					$parentClass = $this->getModel()->getMap()->getTableParentClass($index);
					$lastId = $this->getModel()->getConnection()->last_id();
					$parentField = $this->getModel()->getMap()->getClassParentField($parentClass);
					$parentColumn = $parentField['field']['column'] != null && !empty($parentField['field']['column']) ? sprintf(', %s', $parentField['field']['column']) : '';
					$parentValue = $parentField['field']['column'] != null && !empty($parentField['field']['column']) ? sprintf(", '%s'",$lastId) : '';
					$value = str_replace('[#__fieldLink#]', $parentColumn, $value);
					$value = str_replace('[#__fieldLinkValue#]',$parentValue, $value);
				}
				$this->execute($value);
			}
			//$this->getModel()->get($this->getModel()->getConnection()->last_id());
			$field = $this->getMap()->getPrimaryKey();
			$lastId = $this->getModel()->getConnection()->last_id();
			$this->getMap()->setFieldValue($field['field']['alias'],$lastId);
		} else {
			return false;
		}
	}
	
	public function prepareInsert() {
		//Globals
		$pkField = $this->getModel()->getMap()->getPrimaryKey();
		$parentFields = $this->getModel()->getMap()->getParentFields();
		$parentClass = get_parent_class($this->getModel());
		
		
		//Join the extended classes
		foreach($parentFields as $index => $value) {
			$classVars = get_class_vars($parentClass);
			$this->getModel()->join($classVars['_tablename'],$pkField['field']['column'],$value['field']['column'],'=');
			unset($classVars);
		}
		
		foreach ($this->getModel()->getMap()->fields as $field => $infos) {
			if($this->getModel()->getMap()->getRelationShip($field) != true) {
				$this->getModel()->getMap()->setFieldValue($field, $this->getModel()->$field);
				$value = $this->getModel()->getMap()->getFieldValue($field);
				if(isset($value) && $value != null) {
					$insertFields[$infos['field']['tableReference']] .= $insertFields[$infos['field']['tableReference']] == null ? '' : ', ';
					$insertFields[$infos['field']['tableReference']] .= $infos['field']['tableReference'] . '.' . $infos['field']['column'];
					$insertValues[$infos['field']['tableReference']] .= $insertValues[$infos['field']['tableReference']] == null ? '' : ', ';
					$insertValues[$infos['field']['tableReference']] .= sprintf("'%s'", $value);
				}
			} else if($this->getModel()->getMap()->getRelationShip($field) == true && !empty($this->getModel()->$field)) {
				//print $field . "<br/>";
				$this->getModel()->$field->save();
			}
		}
		
		//Define sqls based on each table from the parent to the child
		foreach($insertFields as $index => $insertFieldsUnique) {
			$sql[$index] = sprintf("INSERT INTO %s ( %s [#__fieldLink#] ) VALUES ( %s [#__fieldLinkValue#] ) ", $index, $insertFieldsUnique, $insertValues[$index]);
		}
		
		//Pre-defined parms
		$tableName = &$this->getModel()->_tablename;
		
		//Constructing the SQL
		return $sql;
		//return $sql = sprintf("INSERT INTO %s ( %s ) VALUES ( %s ) ", $tableName, $insertFields, $insertValues);
	}
	
	public function prepareUpdate() {
		$updatedFields = null;
		//Checking each MAPPED field looking in cache for changes in field value, if existis it will be updated, if not we just update the right fields
		foreach ($this->getMap()->fields as $field => $infos) {
			if($this->getModel()->getMap()->getRelationShip($field) != true && $this->getModel()->$infos['field']['alias'] != $infos['#value']) {
				$this->getMap()->setFieldValue($field, $this->getModel()->$field);
				$updatedFields[$infos['field']['tableReference']] .= $updatedFields[$infos['field']['tableReference']] == null ? '' : ', ';
				$updatedFields[$infos['field']['tableReference']] .= sprintf("%s='%s'", $infos['field']['column'], ($this->getModel()->$field));
			} else if($this->getModel()->getMap()->getRelationShip($field) == true && !empty($this->getModel()->$field)) {
				//print $field . "<br/>";
				$this->getModel()->$field->save();
			}
		}
		
		//Pre-defined parms
		//$tableName = &$this->getModel()->_tablename;
		
		//To see more about pkField Structure see addField at MapObject
		$pkField = &$this->getMap()->getPrimaryKey();
		
		//Define sqls based on each table from the parent to the child
		if(count($updatedFields) > 0) {
			foreach($updatedFields as $index => $updatedFieldsUnique) {
				$pkField = $index == $this->getModel()->_tablename ? $this->getMap()->getPrimaryKey() : $this->getMap()->getTableParentField($index);
				//print $index;
				//print_r($pkField);
				
				$sql[$index] = $updatedFields != null ? sprintf("UPDATE %s SET %s WHERE %s='%s';", $index, $updatedFieldsUnique, $pkField['field']['column'], $pkField['#value']) : null;
			}
		}
		
		//Constructing the SQL
		//$sql = $updatedFields != null ? sprintf("UPDATE %s SET %s WHERE %s='%s'", $tableName, $updatedFields, $pkField['field']['column'], $pkField['#value']) : null;
		
		$modelName = get_class($this->getModel());
		if($sql == null) {
			PhpBURN_Message::output("[!There is nothing to save in model!]: <b>$modelName</b>",PhpBURN_Message::WARNING);
			return array();
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
		if($this->getPointer() <= $this->getLast()) {
			$this->pointer++;
			
			return $this->pointer;
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
			
			return $this->pointer;
		} else {
			return false;
		}
	}
	
	/**
	 * (non-PHPdoc)
	 * @see app/libs/Dialect/IDialect#moveFirst()
	 */
	public function moveFirst() {
		return $this->pointer = 0;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see app/libs/Dialect/IDialect#moveLast()
	 */
	public function moveLast() {
		return $this->pointer = $this->getLast();
	}
	
	/**
	 * (non-PHPdoc)
	 * @see app/libs/Dialect/IDialect#getLast()
	 */
	public function getLast() {
		return $this->getAmount() - 1;
	}
	
	public function getAmount() {
		return $this->getConnection()->num_rows($this->resultSet);
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