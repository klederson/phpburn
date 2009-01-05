<?php
/**
 * All phpBurn classes should extend this
 */
abstract class PhpBURN_Core implements IPhpBurn {
	
	//Relationship types
	const ONE_TO_ONE = 1;
	const ONE_TO_MANY = 2;
	const MANY_TO_MANY = 3;
	
	protected $_connObj = null;
	public  $_mapObj = null;
	protected $_dialectObj = null;
	
	/**
	 * Persistent methods attributes
	 */
	protected $_where = array();
	protected $_orderBy = null;
	protected $_limit = null;
	
	protected $_join = null;
	protected $_joinLeft = null;
	protected $_joinRight = null;
	protected $_joinInner = null;
	
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
		
		//Mapping the object
		$mappingManager = new PhpBURN_Mapping();
		$mappingManager->create($this);
		
		//Setting Up the connection Obj
		$connManager = new PhpBURN_Connection();
		$this->_connObj = clone $connManager->create(PhpBURN_Configuration::getConfig($this->_package));
		
		//Setting Up the dialect Obj
		//@TODO Organizar a seleção do dialeto
		$dialectManager = new PhpBURN_Dialect();
		$this->_dialectObj = clone $dialectManager->create(PhpBURN_Configuration::getConfig($this->_package),$this);
	}
	
	public function __destruct() {
		unset($this->_connObj, $this->_mapObj, $this->_dialectObj);
	}
	
	public function getConnectionObj() {
		return $this->connObj;
	}

	public function find($sql) {
		
	}
	
	public function where($condition,$overrideOthers = false) {
		
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