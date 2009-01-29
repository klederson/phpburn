<?php
/**
 * All phpBurn classes should extend this
 */
abstract class PhpBURN_Core implements IPhpBurn {
	/* The structure of the constants follow the concept
	 * The two first numbers identify the TYPE of constant for example:
	 * 100001, 10 means that integer corresponds to a SQL DATABASE constant, 00 means it corresponds to an QUERY and 01 at the end corresponds to the SELECT query
	 * For more information see the detailed documentation with all constants indexes.
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
	protected $_connObj = null;
	public  $_mapObj = null;
	protected $_dialectObj = null;
	
	//Persistent methods storage
	public $_where = array();
	protected $_orderBy = null;
	protected $_limit = null;
		//join storage
		protected $_join = array();
		protected $_joinLeft = array();
		protected $_joinRight = array();
		protected $_joinInner = array();
	
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
	public $_multiMap = false;
	
	public function __construct() {
		if(!isset($this->_tablename) || !isset($this->_package)) {
			throw new PhpBURN_Exeption(PhpBURN_Message::EMPTY_PACKAGEORTABLE);
		}
		
		//Mapping the object ( _mapObj )
		PhpBURN_Mapping::create($this);
		
		//Setting Up the connection object ( _connObj )
		$this->_connObj = &PhpBURN_Connection::create(PhpBURN_Configuration::getConfig($this->_package));
		
		//Setting Up the dialect object ( _dialectObj )
		$this->_dialectObj = clone PhpBURN_Dialect::create(PhpBURN_Configuration::getConfig($this->_package),$this);
		
		//Add Exception Object
		$this->_exceptionObj = new PhpBURN_Exception();
		
		//Set default output
		$this->_exceptionObj->setOutput(PhpBURN_Exception::FIREBUG);
		
		//Then now we have all set... let's rock!
		$this->_initialize();
	}
	
	final private function _initialize() {
		//Opening the database connection for this object
		$this->_connObj->connect();
	}
	
	/**
	 * Cleaning up the memory
	 */
	public function __destruct() {
		//Cleaning memory and activating __destruct triggers
		unset($this->_connObj, $this->_mapObj, $this->_dialectObj);
	}
	
	/**
	 * Just a method to get the object into _connObj attribute
	 * @return PhpBURN_Connection_*
	 */
	public function getConnectionObj() {
		return $this->_connObj;
	}

	/**
	 * This method search a content based in many arguments like: where, order, primary key, etc.
	 * 
	 * @param String $sql
	 * @return Integer
	 */
	public function find($pk = null) {
		$this->_dialectObj->find($pk);
		
		return $resultSetAmount;
	}
	
	/**
	 * This function is going to retrive you the prepared QUERY for execution based on your dialect (MySQL, PostgreeSQL, Oracle, SQLite, etc )
	 * 
	 * The original idea is from Hugo Ferreira da Silva in the Lumine Base code we just take and re-design it to our needs.
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
				return $this->_dialectObj->_getSelectQuery();
			
			case self::QUERY_SELECT_COUNT:
				return $this->_dialectObj->_getSelectQuery(true, $opt);
				
			case self::QUERY_UPDATE:
				return $this->_dialectObj->_getUpdateQuery( $opt );
				
			case self::QUERY_DELETE:
				return $this->_dialectObj->_getDeleteQuery( $opt );
			
			case self::QUERY_INSERT:
				return $this->_dialectObj->_getInsertQuery( $opt );

			case self::QUERY_MULTI_INSERT;
				return $this->_dialectObj->_getMultiInsertQuery( $opt );
		}
		
		//@TODO Insert here an exeption message: "[!Unsuported SQL type!]: $type"
		print "[!Unsuported SQL type!]: $type";
		exit();
	}
	
	/**
	 * Validate Field(s) value(s) based on mapping instructions and dialect rules
	 * 
	 * @param String $fieldName
	 * @return Boolean
	 */
	public function validateFields($fieldName = null) {
		if( $fieldName == null ) {
			//Validate all fields
			foreach($this->_mapObj->fields as $fieldIndex => $fieldContent) {
				$this->_mapObj->validateField($fieldIndex);
			}
		} else {
			//Validate an specific field
			$this->_mapObj->validateField($fieldName);
		}
		
		return true;
	}
		
	/**
	 * This method allow your model to add various WHERE conditions before your get, search or find call.
	 * 
	 * @param String $condition_start
	 * @param String $stringOperator
	 * @param String/Integer $conditon_end
	 * @param Boolean $override
	 */
	public function where($condition_start, $stringOperator, $conditon_end, $condition = "AND", $override = false) {
		
		$this->_exceptionObj->log('teste');
		
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
	
	public function fetch() {
		$result = $this->dialect->fetch();
		if ($result) {
			foreach ($result as $key => $value) {
				$this->$key = $value;
			}
		}
		return $result;
	}
		
	public function get() {
	}
	
	public function save() {
		$fields = array();
		// if query is an insert
		$insert = true;
		foreach ($this->_mapObj->fields as $field => $infos) {
			$fields[$field] = $this->_mapObj->getFieldValue($field);
			if ($insert==true && isset($infos['pk']) && $infos['pk']==true && !empty($fields[$field])) {
				// query will be an update
				$insert = false;
			}
		}
		//execute query.
	}
	
	public function delete() {
		
	}
	
	public function order() {
		
	}
	
	public function limit() {
		
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
	 * @param String $linkName
	 * @param String $field
	 * @param String $condition
	 */
	public function _linkWhere($linkName, $field, $condition) {
		
	}
	
	/**
	 * It sets a limit or pagination in you link call
	 *
	 * @param String $linkName
	 * @param Integer $start
	 * @param Integer $end
	 */
	public function _linkLimit($linkName, $start, $end = null) {
		
	}
	
	/**
	 * It creates a order into your link list
	 *
	 * @param String $linkName
	 * @param String $field
	 * @param String $orderType
	 */
	public function _linkOrder($linkName, $field, $orderType = "ASC") {
		
	}
}
?>
