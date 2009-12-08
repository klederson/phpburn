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
	
	public function migrate(PhpBURN_Core $model, $execute = true) {
		print "<pre>";
		foreach($model->getMap()->fields as $fieldIndex => $fieldContent) {			
			
			
			if(!$model->getMap()->isRelationship($fieldIndex) && !$model->getMap()->isParent($fieldIndex)) {
	//			Checking and fixing by patterns the length FIELDTYPE(LENGTH)
//				$fieldContent['field']['length'] = $fieldContent['field']['length'] == null || empty($fieldContent['field']['length']) ? 0 : $fieldContent['field']['length']; //determine if is not set
				
				unset($options);
				foreach($fieldContent['field']['options'] as $optionIndex => $optionContent) {
					$options .= !empty($options) && $options != null ? ' ' : '';
					$options .= $this->fieldOptionToString($optionIndex,$optionContent, $fieldContent);
				}
				$fieldString .= $fieldString == null ?  '' : ", \r\n";
				
				
				switch(strtolower($fieldContent['field']['type'])) {
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
						$fieldContent['field']['length'] = explode(',',$fieldContent['field']['length']);
						unset($length);
						foreach($fieldContent['field']['length'] as $value) {
							$length .= $length != null ? ', ' : '';
							$length .= sprintf("'%s'",$value);
						}
						$length = sprintf("(%s)", $length);
					break;
					default:
						$fieldContent['field']['length'] = is_numeric(str_replace(',','.',$fieldContent['field']['length'])) ? $fieldContent['field']['length'] : sprintf("'%s'",$fieldContent['field']['length']); //determine if is int, float or string
						$length = sprintf("(%s)", $fieldContent['field']['length']);
					break;
				}
				
				
				$fieldString .= sprintf("\t`%s` %s%s %s",$fieldContent['field']['column'],strtoupper($fieldContent['field']['type']), $length, $options);
			} else if($model->getMap()->isParent($fieldIndex)) {
				
			}
		}
		
		$sql = sprintf("CREATE TABLE `%s`.`%s` ( \r\n $fieldString \r\n); \r\n\r\n", $model->getConnection()->getDatabase(), $model->_tablename);
		
		if($execute == true) {
			return $model->getDialect()->execute($sql);
		} else {
			return $sql;
		}
	}
	
	private function fieldOptionToString($option, $value, $fieldContent) {
		switch($option) {
			case 'autoincrement':
			case 'auto_increment':
				return "AUTO_INCREMENT";
			break;
			case 'notnull':
			case 'not_null':
					return 'NOT NULL';
			break;
			case 'null':
					return 'NULL';
			break;
			case 'primary':
				return "PRIMARY KEY";
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