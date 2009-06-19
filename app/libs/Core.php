<?php
/**
 * All phpBurn classes should extend thi
 * 
 * @author Kléderson Bueno <klederson@klederson.com>
 * @version 0.37
 */
abstract class PhpBURN_Core implements IPhpBurn {
	/* The structure of the constants follow the concept
	 * The two first numbers identify the TYPE of constant for example:
	 * 100001, 10 means that integer corresponds to a SQL DATABASE constant, 00 means it corresponds to an QUERY and 01 at the end corresponds to the SELECT query
	 * For more information see the detailed documentation with all constants indexes.
	 * 
	 * TABLE OF REFERENCE:
	 * 10XXXX = SQL DATABASE
	 * 1000XX = QUERY TYPE
	 * 1001XX = QUERY TYPE RELATIONSHIP
	 * 1002XX = DATABASE CONNECTION
	 * 
	 * It has been made to make easier to identify an number in debugs and other stuffs.
	 */
	
	//Relationship types
	const ONE_TO_ONE 						= 100101;
	const ONE_TO_MANY 						= 100102;
	const MANY_TO_MANY 					= 100103;
	
	//Query types
	//@TODO We do not use the term SQL because in the future we want to expand phpBURN to NON-SQL databases and/or even possibles new kinds of database such as CouchDB
	const QUERY_SELECT						= 100001;
	const QUERY_SELECT_COUNT			= 100002;
	const QUERY_UPDATE						= 100003;
	const QUERY_INSERT						= 100004;
	const QUERY_DELETE						= 100005;
	const QUERY_MULTI_INSERT			= 100006;
	
	//Internal objects
	public $_connObj							= null;
	public $_mapObj							= null;
	public $_dialectObj							= null;
	
	//Fields mapping
	public $_fields								= array();
	
	//Persistent methods storage
	public $_where								= array();
	public $_orderBy							= null;
	public $_limit									= null;
	public $_select								= array();
	public $_join									= array();
	
	/**
	 * This is an automatic configuration when a model inherit another PhpBURN Model
	 * than the model will use two or more mapItens. 
	 * @example class MyNewModel extends ParentModel {
	 * @example	
	 * @example }
	 * 
	 * @example class ParentModel extends PhpBURN_Core {
	 * @example	
	 * @example }
	 * 
	 * @var Boolean
	 */
	public $_multiMap							= false;
	
	public function __construct() {
		if(!isset($this->_tablename) || !isset($this->_package)) {
			throw new PhpBURN_Exeption(PhpBURN_Message::EMPTY_PACKAGEORTABLE);
		}
		
		//Mapping the object ( _mapObj )
		PhpBURN_Mapping::create($this);
		
		//Setting Up the connection object ( _connObj )
		//$this->_connObj = &PhpBURN_ConnectionManager::create(PhpBURN_Configuration::getConfig($this->_package));
		
		//Setting Up the dialect object ( _dialectObj )
		$this->_dialectObj = clone PhpBURN_DialectManager::create(PhpBURN_Configuration::getConfig($this->_package),$this);
		
		//Then now we have all set... let's rock!
		$this->_initialize();
	}
	
	final private function _initialize() {
		//Opening the database connection for this object
		$this->getConnection()->connect();
	}
	
	/**
	 * Cleaning up the memory
	 */
	public function __destruct() {
		//Cleaning memory and activating __destruct triggers
		unset($this->_connObj, $this->_mapObj, $this->_dialectObj);
	}

	/**
	 * This method search a content based in many arguments like: where, order, primary key, etc.
	 * 
	 * @param String $sql
	 * @return Integer
	 */
	public function find($pk = null) {		
		return $this->getDialect()->find($pk);
	}
	
	/**
	 * This function is going to retrive you the prepared QUERY for execution based on your dialect (MySQL, PostgreeSQL, Oracle, SQLite, etc )
	 * 
	 * The original idea is from Hugo Ferreira da Silva in the Lumine Base code we just take and re-design it to our needs.
	 * 
	 * @author Kléderson Bueno <klederson@klederson.com>
	 * @version 0.1a
	 * 
	 * @param Integer $type
	 * @param Mixed $opt
	 * @return String
	 */
	public function _getQUERY( $type = self::QUERY_SELECT, $opt = null )
	{
		switch($type)
		{
			case self::QUERY_SELECT:
				return $this->getDialect()->_getSelectQuery();
			
			case self::QUERY_SELECT_COUNT:
				return $this->getDialect()->_getSelectQuery(true, $opt);
				
			case self::QUERY_UPDATE:
				return $this->getDialect()->_getUpdateQuery( $opt );
				
			case self::QUERY_DELETE:
				return $this->getDialect()->_getDeleteQuery( $opt );
			
			case self::QUERY_INSERT:
				return $this->getDialect()->_getInsertQuery( $opt );

			case self::QUERY_MULTI_INSERT;
				return $this->getDialect()->_getMultiInsertQuery( $opt );
		}
		
		//@TODO Insert here an exeption message: "[!Unsuported SQL type!]: $type"
		print "[!Unsuported SQL type!]: $type";
		exit();
	}
	
	/**
	 * join Function inserts a JOIN clause in the get()/find() method and than returns the join result in a array into the object
	 * Ex. $obj->join('users');
	 * returns $obj->_users->name and $obj->_users->login (but only as object not a PhpBURN model if you want methods in user use _getLink())
	 * 
	 * @author Kléderson Bueno <klederson@klederson.com>
	 * @version 0.1a
	 * 
	 * @param String $tableName
	 * @param String $fieldLeft
	 * @param String $fieldRight
	 * @param String $operator
	 * @param String $joinType
	 */
	public function join($tableName, $fieldLeft = null, $fieldRight = null, $operator = '=', $joinType = 'JOIN') {
		$this->_join[$tableName]['fieldLeft'] = $fieldLeft;
		$this->_join[$tableName]['fieldRight'] = $fieldRight;
		$this->_join[$tableName]['operator'] = $operator;
		$this->_join[$tableName]['type'] = $joinType;
	}
	
	public function joinLeft($tableName, $fieldLeft = null, $fieldRight = null, $operator = '=') {
		$this->join($tableName, $fieldLeft = null, $fieldRight = null, $operator = '=', $joinType = 'LEFT JOIN');
	}
	
	public function joinRight($tableName, $fieldLeft = null, $fieldRight = null, $operator = '=') {
		$this->join($tableName, $fieldLeft = null, $fieldRight = null, $operator = '=', $joinType = 'RIGHT JOIN');
	}
	
	public function joinInner($tableName, $fieldLeft = null, $fieldRight = null, $operator = '=') {
		$this->join($tableName, $fieldLeft = null, $fieldRight = null, $operator = '=', $joinType = 'INNER JOIN');
	}
	
	public function joinOutter($tableName, $fieldLeft = null, $fieldRight = null, $operator = '=') {
		$this->join($tableName, $fieldLeft = null, $fieldRight = null, $operator = '=', $joinType = 'OUTTER JOIN');
	}
	
	/**
	 * Validate Field(s) value(s) based on mapping instructions and dialect rules
	 * 
	 * @author Kléderson Bueno <klederson@klederson.com>
	 * @version 0.1a
	 * 
	 * @param String $fieldName
	 * @return Boolean
	 */
	public function validateFields($fieldName = null) {
		if( $fieldName == null ) {
			//Validate all fields
			foreach($this->getMap()->fields as $fieldIndex => $fieldContent) {
				$this->getMap()->validateField($fieldIndex);
			}
		} else {
			//Validate an specific field
			$this->getMap()->validateField($fieldName);
		}
		
		return true;
	}
	
	public function where($conditions) {
		array_push($this->_where, $conditions);
	}
	
	public function select($field, $alias = null) {
		$alias = $alias == null ? $field : $alias;
		array_push($this->_select, array('value'=>$field, 'alias'=>$alias));
	}
		
	/**
	 * SuperWhere (swhere)
	 * 
	 * This method allow your model to add various WHERE conditions before your get, search or find call.
	 * However it uses a new way of define your conditions and keep ALL compatibility when database change.
	 * 
	 * @author Kléderson Bueno <klederson@klederson.com>
	 * @version 0.1a
	 * 
	 * @param String $condition_start
	 * @param String $stringOperator
	 * @param String/Integer $conditon_end
	 * @param Boolean $override
	 */
	public function swhere($condition_start, $stringOperator, $conditon_end, $condition = "AND", $override = false) {
		
		//$this->_exceptionObj->log('teste');
		
		$conditions = array();
		$conditions['start'] = $condition_start;
		$conditions['end'] = $conditon_end;
		$conditions['operator'] = $this->convertWhereOperators($stringOperator);
		$conditions['condition'] = $condition;
		
		if($override == true) {
			unset($this->_where);
		}
		
		array_push($this->_where, $conditions);

	}
	
	/**
	 * Here we setup the operators table, that translate the spoken language into a programatic operator. It will be used in database queries.
	 * @var Array
	 */
	private $operatorsTable = array(
		">" => array('>', 'major', 'maior'),
		"<" => array('<','minor', 'menor'),
		"!=" => array('!=','diff', 'different', 'diferente'),
		"=" => array('=','equal','eq','igual'),
		">=" => array('>=','major_equal', 'major_eq', 'maior_igual'),
		"<=" => array('<=','minor_equal', 'minor_eq', 'menor_igual')
	);
	
	/**
	 * Searchs the native spoken language operator and converts into a programatic operator based on $this->operatorsTable .
	 * FIXME Discover another ( and more inteligent ) solution for this case and also move the operatorsTable too.
	 * 
	 * @author Kléderson Bueno <klederson@klederson.com>
	 * @version 0.1a
	 * 
	 * @param String $operator
	 * @return String
	 */
	private function convertWhereOperators($operator) {
		$operator = strtolower($operator);
		
		foreach($this->operatorsTable as $operatorIndex => $content) {
			foreach($content as $value) {
				if($value == $operator) {
					return $operatorIndex;
				}
			}
		}
		
		return $operator;
	}
	
	/**
	 * fetch() moves the cursor to the next result into the dataset
	 * 
	 * @author Kléderson Bueno <klederson@klederson.com>
	 * @version 0.1a
	 * 
	 * @return PhpBURN_Core
	 */
	public function fetch() {
		$result = $this->getDialect()->fetch();
		if ($result) {
			foreach ($result as $key => $value) {
				$this->getMap()->setFieldValue($key,$value);
			}
		}
		return $result;
	}
		
	public function get() {
	}
	
	public function save() {
		$this->getDialect()->save();
	}
	
	public function delete() {
		
	}
	
	public function order() {
		
	}
	
	public function limit($offset = null, $limit = null) {
		$this->_limit = $limit == null ? $offset : $offset . ',' . $limit;
	}
	
	//Relationships functions
	
	public function _getLink($name, $linkWhere = null) {
		$parms = func_get_args();
		
		if($linkWhere != null && isset($linkWhere)) {
			$this->_linkWhere($linkWhere);
		}
		
		if(isset($parms[2])) {		
			$this->_linkLimit($parms[2],$parms[3]);	
		}
	}
	
	/**
	 * It puts a WHERE clause when you want to get a link with specific caracteristics
	 * 
	 * @author Kléderson Bueno <klederson@klederson.com>
	 * @version 0.1a
	 *
	 * @param String $linkName
	 * @param String $field
	 * @param String $condition
	 */
	public function _linkWhere($linkName, $field, $condition) {
		
	}
	
	/**
	 * It sets a limit or pagination in you link call
	 * 
	 * @author Kléderson Bueno <klederson@klederson.com>
	 * @version 0.1a
	 *
	 * @param String $linkName
	 * @param Integer $start
	 * @param Integer $end
	 */
	public function _linkLimit($linkName, $offset = null, $limit = null) {
		
	}
	
	/**
	 * It creates a order into your link list
	 * 
	 * @author Kléderson Bueno <klederson@klederson.com>
	 * @version 0.1a
	 *
	 * @param String $linkName
	 * @param String $field
	 * @param String $orderType
	 */
	public function _linkOrder($linkName, $field, $orderType = "ASC") {
		
	}
	
	/**
	 * Auxiliar Method : Begins a Transaction
	 */
	public function begin() {
		$this->getConnection()->begin();
	}
	
	/**
	 * Auxiliar Method : Begins a Transaction
	 */
	public function commit() {
		$this->getConnection()->commit();
	}
	
	/**
	 * Auxiliar Method : Begins a Transaction
	 */
	public function rollback() {
		$this->getConnection()->rollback();
	}
	
	/**
	 * Auxiliar Method : Gets the Map Object for the model
	 * @return PhpBURN_Map
	 */
	public function getMap() {
		return $this->_mapObj;
	}
	
	/**
	 * Auxiliar Method : Gets the Dialect Object for the model
	 * @return PhpBURN_Dialect_(DatabaseType)
	 * @see app/libs/Dialect(Folder)
	 */
	public function getDialect() {
		return $this->_dialectObj;
	}
	
	/**
	 * Auxiliar Method : Gets the Connection Object for the model
	 * @return PhpBURN_Connection_(DatabaseType)
	 * @see app/libs/Connection(Folder)
	 */
	public function getConnection() {
		//return $this->_connObj;
		return PhpBURN_ConnectionManager::create(PhpBURN_Configuration::getConfig($this->_package));
	}
}
?>
