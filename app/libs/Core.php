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
		//TODO Organizar a seleção do dialeto
		$dialectManager = new PhpBURN_Dialect();
		$this->_dialectObj = clone $dialectManager->create(PhpBURN_Configuration::getConfig($this->_package),$this);
	}
	
	public function __destruct() {
		unset($this->connObj);
	}
	
	public function getConnectionObj() {
		return $this->connObj;
	}

	public function find($sql) {
		
	}
	
	public function where($field,$condition) {
		
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
	
	//Relationships functions
	public function _linkWhere($linkName, $field, $condition) {
		
	}
	
	public function _linkLimit($start, $end = null) {
		
	}
	
	public function _linkOrder($field, $orderType = "ASC") {
		
	}
}
?>