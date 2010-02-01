<?php
PhpBURN::load('Dialect.Dialect');
PhpBURN::load("Dialect.IDialect");

/**
 * This class manages all content, queries and result stuff in a system level and for all database driver interaction level
 * it calls PhpBURN_Connection object that is the main responsable between the application data and database interaction
 * 
 * @package PhpBURN
 * @subpackage Dialect
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
		return $this->getModel()->getConnection()->affected_rows();
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
		$innerSQL = sprintf("%s %s %s %s %s %s ",$fields, $from, $joinString, $conditions, $whereConditions, $orderConditions);
		
		if($offset == null && $limit == null)
		{
			return;
		} else if($offset == null && $limit != null) {
			$sql = sprintf("SELECT TOP %s * FROM ( SELECT ROW_NUMBER() OVER (%s) AS RowNumber, %s ) AS  _MyResults", $limit, $orderConditions, $innerSQL);
		} else if($offset != null && $limit == null) {
			$sql = sprintf("SELECT TOP %s * FROM ( SELECT ROW_NUMBER() OVER (%s) AS RowNumber, %s ) AS  _MyResults", $offset, $orderConditions, $innerSQL);
		} else {
			$sql = sprintf("SELECT TOP %s * FROM ( SELECT ROW_NUMBER() OVER (%s) AS RowNumber, %s ) AS  _MyResults WHERE  RowNumber > %s", $limit, $orderConditions, $innerSQL, $offset);
		}
		
		return $sql;
	}
	
	public function buildSELECTQuery($fields, $from, $joinString, $conditions, $whereConditions, $orderConditions, $limit, $extras = null) {
		if($limit != null) {
			return $limit;
		} else {
			return ('SELECT ' . $fields . ' ' . $from . ' ' . $joinString . ' ' . $conditions . ' ' . $whereConditions . ' ' . $orderConditions . ';');
		}
	}
	
	/* Auxiliar Methods */
	
	protected function handleSelect() {
		
	}
	
	public function callStoredProcedure($name,$attributes = array(), $alias = null) {
		$alias = $alias = null ? $name : $alias;
	}

	public function setMode($mode = "") {
		$this->getConnection()->mode = $mode;
		
		if (empty($mode))
			$this->getConnection()>mode(PDO::FETCH_ASSOC);
	}
	
	
	/* Other Methods */
	
//	@TODO: Create this method
	public function getErrorMsg() {
		
	}
	
	public function migrate($execute = true) {
		foreach($this->getModel()->getMap()->fields as $fieldIndex => $fieldContent) {			
			
			if($this->getModel()->getMap()->isField($fieldIndex,true) ) {				
//				Determine field options
				unset($options);
				foreach($fieldContent['field']['options'] as $optionIndex => $optionContent) {
					$options .= !empty($options) && $options != null ? ' ' : '';
					$options .= $this->fieldOptionToString($optionIndex,$optionContent, $fieldContent);
				}
				$fieldString .= $fieldString == null ?  '' : ", \r\n";
				
				$length = $this->lengthToString($fieldContent['field']['type'], $fieldContent['field']['length']);				
				
				$fieldString .= sprintf("\t`%s` %s%s %s",$fieldContent['field']['column'],strtoupper($fieldContent['field']['type']), $length, $options);
			} else if($this->getModel()->getMap()->isParent($fieldIndex)) {
				
			}
		}
		
		$sql = sprintf("CREATE TABLE `%s`.`%s` ( \r\n $fieldString \r\n); \r\n\r\n", $this->getModel()->getConnection()->getDatabase(), $this->getModel()->_tablename);
		
		if($execute == true) {
			return $this->getModel()->getDialect()->execute($sql);
//			print "<pre>$sql</pre>";
		} else {
			return $sql;
		}
	}
	
	private function lengthToString($fieldType, $fieldLength) {
		switch(strtolower($fieldType)) {
			case 'tinyblob':
			case 'blob':
			case 'mediumblob':
			case 'longblob':
			case 'tinytext':
			case 'text':
			case 'mediumtext':
			case 'longtext':
			case 'date':
			case 'timestamp':
				$length = '';
			break;
			case 'enum':
				$fieldLength = explode(',',$fieldLength);
				unset($length);
				foreach($fieldLength as $value) {
					$length .= $length != null ? ', ' : '';
					$length .= sprintf("'%s'",$value);
				}
				$length = sprintf("(%s)", $length);
			break;
			default:
				$fieldLength = is_numeric(str_replace(',','.',$fieldLength)) ? $fieldLength : sprintf("'%s'",$fieldLength); //determine if is int, float or string
				$length = sprintf("(%s)", $fieldLength);
			break;
		}
		
		return $length;
	}
	
	private function fieldOptionToString($option, $value, $fieldContent) {
//					print_r($fieldContent);
//			print "<hr/>";
		switch($option) {
			case 'autoincrement':
			case 'auto_increment':
				if(!$this->getModel()->getMap()->isParentKey($fieldContent['field']['name']))
					return "AUTO_INCREMENT";
				else
					return null;
			break;
			case 'notnull':
			case 'not_null':
					return 'NOT NULL';
			break;
			case 'null':
					return 'NULL';
			break;
			case 'primary':
					if(!$this->getModel()->getMap()->isParentKey($fieldContent['field']['name']))
						return "PRIMARY KEY";
					else 
						return null;
			break;
			case 'defaultvalue':
			case 'default_value':
				if($fieldContent['field']['type'] != 'timestamp') {
					$value = sprintf("'%s'",$value);
				}
				return sprintf("DEFAULT %s",$value);
			break;
		}
	}
	
	private function generateCreateTableSQL(PhpBURN_Core $model) {
		
	}
}
?>