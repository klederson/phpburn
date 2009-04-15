<?php
abstract class PhpBURN_Dialect_Operations {
	
	/**
	 * Prepares the SQL Query for SELECT complete with Joins and all needed for a SELECT.
	 * 
	 * @author Kléderson Bueno <klederson@klederson.com>
	 * 
	 * @return String $sql
	 */
	protected function prepareSelect() {
		$select = 'SELECT ';
		
		//Creating the selectable fields
		if(count($this->obj->_select) <= 0) {
			//Selecting from the map
			foreach($this->obj->_mapObj->fields as $index => $value) {
				//Parsing non-relationship fields
				if(!$value['isRelationship'] && $value['field']['column'] != null) {
					$fields .= $fields == null ? "" : ", ";
					$fields .=  $this->obj->_tablename . '.' . $value['field']['column'] . ' AS ' . $index;
				}
			}
		} elseif(count($this->obj->_select) > 0) {
			//Select based ONLY in the $obj->select(); method
			foreach($this->obj->_select as $index => $value) {
				$fields .= $fields == null ? "" : ", ";
				$fields .= $value['value'] . ' AS ' . $value['alias'];
			}
		} else {
			$model = get_class($this->obj);
//		@TODO Insert here an exeption message: "[!$model is not an mapped or valid PhpBURN Model!]"			
			print "[!$model is not an mapped or valid PhpBURN Model!]";
			exit;
		}
		
		//Defnine FROM tables
		$from = 'FROM ' . $this->obj->_tablename;
		
		if(count($this->obj->_join) > 0) {
			foreach($this->obj->_join as $index => $value) {
				$joinString .= $joinString != null ? ' ' : null;
				$joinString .= $value['type'] .' '. $index;
				if($value['fieldLeft']  != null && $value['fieldRight']  != null) {
					$joinString .= ' ON '. "'" .$value['fieldLeft'] ."'". $value['operator'] ."'". $value['fieldRight'] ."'";
				}
			}			
		}
				
		if(count($this->obj->_where) > 0) {
			//Define conditions
			$conditions = 'WHERE ';
			foreach($this->obj->_where as $index => $value) {
				//Checking swhere and where
				if(!is_array($value)) {
					//Normal where
					$whereConditions .= $value;
				} else {
					//SuperWhere
					$whereConditions .= $whereConditions == null ? "" : $value['condition'];
					$whereConditions .= $value['start'] . ' ' . $value['operator'] . ' \'' . $value['end'] . '\' ';
				}
			}
		}
		
		if(count($this->obj->_orderBy) > 0) {
			//Define OrderBY
			$orderBy = 'ORDER BY ';
			foreach($this->obj->_orderBy as $index => $value) {
				$orderConditions .= $orderConditions == null ? "" : ", " . $value['type'];
				$orderConditions .= $value['field'];
			}
		}
		
		if($this->obj->_limit != null) {
			//Define Limit
			$limit = 'LIMIT ' . $this->obj->_limit;
		}
		
		//Construct SQL
		$sql = $select . ' ' . $fields . ' ' . $from . ' ' . $joinString . ' ' . $conditions . ' ' . $whereConditions . ' ' . $orderBy . ' ' . $orderConditions . ' ' . $limit;
		
		return $sql;
	}
	
}
?>