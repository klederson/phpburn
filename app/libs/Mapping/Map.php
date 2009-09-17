<?php
PhpBURN::load('Mapping.IMap');

/**
 * @package PhpBURN
 * @subpackage Mapping
 * 
 * @author Kléderson Bueno <klederson@klederson.com>
 */
class PhpBURN_Map implements IMap {
	
	//Relationship types
	const ONE_TO_ONE 						= 100101;
	const ONE_TO_MANY 						= 100102;
	const MANY_TO_ONE 						= 100103;
	const MANY_TO_MANY 					= 100104;
	
	/**
	 * This attribute carries all mapping information such as relationships, witch field is witch column, etc.
	 * This is mapped only once and is cached for all the objects from the same type.
	 * It means all Teste() Objects only will load the xmlmapping once and not for each as in the others ORMs
	 * 
	 * @example 
	 * $mapping = array(
	 * 								'name' => array( 
	 * 									['field'] => array(type:string, column:string , dataType:string , notnull:bool = null, autoincrement:bool = null, defaultvalue = null),
	 * 									*['retroRelationship'] => array( fieldHere:string, fieldThere:string, class:string, type:const ),
	 * 									**['isRelationship'] => array( type:const, class:string, fieldThere:string, fieldHere:string, lazy:bool )
	 * 									***['isExternal'] => false;
	 * 									***['classReference'] => ( parentPackage, class, table, column )
	 * 
	 * 								) 
	 * 							)
	 * ['field'] defines all field information ( when it is a relationship it just set type = "relationship" and null for all rest )
	 * TODO ['retroRelationship'] Its when you config in the child Class a relationship information, for example Users agregates Albums you only must have to config a relationship in Parent Class. This is for other uses or informations.
	 * ['isRelationship'] defines the kind of relationship it is, if the field is not a relationship this = false else field is an array. This is important for lazy mode use and carries the array of objects from that child class
	 * ['isExternal'] Says to the Map Object that field will be used from another PhpBURN_Core Object ( parent object )
	 * @var array
	 */	
	public $fields = array();
	
	public $parentFieldReferences = array();
	
	/**
	 * Our reference to $modelObj
	 *
	 * @var unknown_type
	 */
	public $modelObj = null;
	
	public function __construct(PhpBURN_Core &$modelObj){
		$this->modelObj = $modelObj;

		//$this->mapThis($this->modelObj);
	}
	
	public function reset() {
		foreach($this->fields as $index => $value) {
			$this->setFieldValue($index,'');
		}
	}
	
	/**
	 * Starts a mapping for the model checking if has a XML mapping or if it uses
	 * a coded mapping.
	 *
	 * @param PhpBURN_Core $modelObj
	 */
	public function mapThis() {
		$xmlMap = $this->getXmlMap($this->modelObj);
		
		if($xmlMap == true) {
			$this->mapFromXML($xmlMap);
		} else {
			$this->mapFromCode();
		}
	}
	
	public function cloneAttributes() {
		return $this->fields;
	}
	
	public function setAttributes(array $fields, $incremental = true) {
		if($incremental == true) {
			foreach($fields as $index => $fieldData) {
				$this->fields[$index] = $fieldData;
			}
		} else {
			$this->fields = $fields;
		}
	}
	
	public function cloneReferences() {
		return $this->parentFieldsReferences;
	}
	
	public function setReferences($fieldsArray) {
		if(count($fieldsArray) > 0) {
			foreach($fieldsArray as $index => $value) {
				$this->parentFieldsReferences[$index] = $value;
			}
		}
		
	}
	
	/**
	 * This maps the model based on its XML Mapping
	 * @TODO Change it to DOMElement instead SimpleXML
	 * @param PhpBURN_Core $modelObj
	 * @param SimpleXMLElement $xmlMap
	 */
	public function mapFromXML(SimpleXMLElement $xmlMap) {
		//Setting up non-relationship fields
		foreach($xmlMap->class->attribute as $indexAttribute => $xmlAttribute) {
			switch($xmlAttribute->attributes()->type) {
				//Defines a column
				case 'column':
					$options = array();
					$options['notnull'] = (string)$xmlAttribute->attributes()->notnull;
					$options['autoincrement'] = (string)$xmlAttribute->attributes()->autoincrement;
					$options['primary'] = (string)$xmlAttribute->attributes()->primary;
					$options['defaultvalue'] = (string)$xmlAttribute->attributes()->defaultvalue;
					
					$this->addField((string)$xmlAttribute->attributes()->name,(string)$xmlAttribute->attributes()->column,(string)$xmlAttribute->attributes()->datatype,(string)$xmlAttribute->attributes()->lenght,$options);
				break;
				//Setup a relationship
				case 'relationship':
					
					/* Setup relationShip type */
					/* FIXME Create a way to call a Model constant */
					switch((string)$xmlAttribute->relationship[0]->attributes()->type) {
						case 'ONE_TO_ONE':
							$relType = self::ONE_TO_ONE;
						break;
						case 'ONE_TO_MANY':
							$relType = self::ONE_TO_MANY;
						break;
						case 'MANY_TO_MANY':
							$relType = self::ONE_TO_MANY;
						break;
					}
					$relType = !is_numeric($relType) ? 1 : $relType;
					/* /Setup relationShip type */
					
					//Setup relationShip informations
					$relName = (string)$xmlAttribute->attributes()->name;
					$foreignClass = (string)$xmlAttribute->relationship[0]->attributes()->class;
					$thisKey = (string)$xmlAttribute->relationship[0]->attributes()->thisKey;
					$relKey = (string)$xmlAttribute->relationship[0]->attributes()->relKey;
					$outKey = (string)$xmlAttribute->relationship[0]->attributes()->outKey;
					$relOutKey = (string)$xmlAttribute->relationship[0]->attributes()->relOutKey;
					$relTable = (string)$xmlAttribute->relationship[0]->attributes()->relTable;
					$lazy = (string)$xmlAttribute->relationship[0]->attributes()->lazy;
					
					//Creating the relationship
					$this->addRelationship($relName,$relType,$foreignClass,$thisKey,$relKey,$outKey,$relOutKey,$relTable,$lazy);
				break;
				/**
				 * Setup a child relationship
				 * 
				 * A child relationship is when you want your child class to "know" about its parent class relationship
				 */
				case 'child':
					
				break;
			}
		}
	}
	
	public function fillModel(array $data) {
		if ($data) {
//			Clean old data
			$this->reset();
			foreach ($data as $key => $value) {
				$this->setFieldValue($key,$value);
			}
		}
		
		return $data;
	}
	
	/**
	 * This maps the model based on its _objectMap() function
	 * @TODO Discutir a redundância aqui e analisar os métodos mágicos que podem vir a ser aplicados
	 */
	public function mapFromCode() {
		$this->modelObj->_mapping();
	}
	
	/**
	 * This method creates a relationship field into the mapping
	 * Relationship fields has different behavior than the column fields
	 * they comunicate with another PhpBURN models connecting them by some
	 * kind of reference.
	 * 
	 * A relationship can be:
	 * - ONE_TO_ONE
	 * - ONE_TO_MANY
	 * - MANY_TO_MAY
	 *
	 * @param String $relName
	 * @param int $relType
	 * @param String $foreignClass
	 * @param String $thisKey
	 * @param String $relKey
	 * @param String $outKey
	 * @param String $relOutKey
	 * @param String $relTable
	 * @param Boolean $lazy
	 */
	public function addRelationship($relName,$relType,$foreignClass, $thisKey, $relKey, $outKey, $relOutKey, $relTable, $lazy = false) {

		//Setup a simple field as a relationship field, just for double check
		$this->fields[$relName]['field']['type'] = false;
		$this->fields[$relName]['field']['column'] = false;
		$this->fields[$relName]['field']['type'] = false;
		$this->fields[$relName]['field']['length'] = false;
		
		//Setup the relationship
		$this->fields[$relName]['isRelationship'] = array();
			$this->fields[$relName]['isRelationship']['alias'] = $relName;
			$this->fields[$relName]['isRelationship']['type'] = $relType;
			$this->fields[$relName]['isRelationship']['foreignClass'] = $foreignClass;
			$this->fields[$relName]['isRelationship']['thisKey'] = $thisKey;
			$this->fields[$relName]['isRelationship']['relKey'] = $relKey;
			$this->fields[$relName]['isRelationship']['outKey'] = $outKey;
			$this->fields[$relName]['isRelationship']['relOutKey'] = $relOutKey;
			$this->fields[$relName]['isRelationship']['relTable'] = $relTable;
			$this->fields[$relName]['isRelationship']['lazy'] = $lazy;
		
		//For child relationships ONLY
		$this->fields[$relName]['isExternal'] = false;
		
		//For multipMap ONLY
		$this->fields[$relName]['classReference'] = !isset($this->fields[$relName]['classReference']) ? get_class($this->modelObj) : $this->fields[$relName]['classReference'];
		//$this->fields[$relName]['parentLinkField'] = 'false'; 
		//Setup defaultvalue for this field
		$this->setFieldValue($relName,null);
	}
	
	public function addParentRelationShip() {
		
	}
	
	public function addParentField($name) {
		$parentClass = get_parent_class($this->modelObj);
		$relName = '__PhpBURN_Extended_'.$parentClass;
				
		$this->addRelationship($relName, PhpBURN_Map::ONE_TO_ONE, $parentClass, $name, $name, null, null, null);
		
		$parentVars = get_class_vars($parentClass);
		$parentTable = $parentVars['_tablename'];
		
		$this->parentFieldsReferences[$parentTable] = $name;
		
		//NOT WOKING YET
		$this->fields[$name]['classReference'] = get_class($this->getModel());
		$this->fields[$name]['parentLinkField'] = true;
		
		
	}
	
	public function getParentFields() {
		if(count($this->parentFieldsReferences) > 0 ) {
			foreach($this->parentFieldsReferences as $name) {
				$fields[] = $this->getField($name);
			}
		} else {
			$fields = array();
		}
		
		return $fields;
	}
	
	public function getTableParentField($tableName) {
		if(array_key_exists($tableName, $this->parentFieldsReferences) == true) {
			return $this->fields[$this->parentFieldsReferences[$tableName]];
		} else {
			return false;
		}
	}
	
	public function getTrueFields($className) {
		return PhpBURN_Mapping::$mapping[$className];
	}
	
	/**
	 * This method creates a field into the model mapping info
	 * It can be called anytime anywhere by the Map Object
	 *
	 * @param String $name
	 * @param String $column
	 * @param String $type
	 * @param String $range
	 * @param Array $options
	 */
	public function addField($name, $column, $type, $length, array $options) {
			$parentClass = get_parent_class($this->modelObj);
		
			//Check for duplicated columns in this map
			array_walk_recursive($this->fields,array($this, 'checkColumns'),$name);
			
			//Setup a simple field		
			$this->fields[$name]['field']['type'] = $type;
			$this->fields[$name]['field']['alias'] = $name;
			$this->fields[$name]['field']['column'] = $column;
			$this->fields[$name]['field']['type'] = $type;
			$this->fields[$name]['field']['length'] = $length;
			$this->fields[$name]['field']['options'] = count($options) > 0 ? $options : array();
			$this->fields[$name]['field']['tableReference'] = $this->modelObj->_tablename;
			
			//Just for double check it sets false to other kinds of field
			$this->fields[$name]['isRelationship'] = false;
			
			//When it belongs to a parent class
			$this->fields[$name]['isExternal'] = false;
			
			//For multipMap use ONLY
			$this->fields[$name]['classReference'] = get_class($this->modelObj);
			//$this->fields[$name]['parentLinkField'] = 'false';
			
			//Setup defaultvalue for this field
			$options['defaultvalue'] = $options['defaultvalue'] != null ? $options['defaultvalue'] : null;
			$this->setFieldValue($name,$options['defaultvalue']);
	}
	
	public function getPrimaryKey() {
		//Check for a PK field
		foreach($this->fields as $index => $content) {
			if($content['field']['options']['primary'] == true) {
				return $content;
			}
		}
		
		$modelName = get_class($this->modelObj);
		PhpBURN_Message::output("<b>$modelName</b> [!has no Primary Key. How did you did it?!]", PhpBURN_Message::EXCEPTION);
	}
	
	public function getRelationShip($name, $returnData = false) {
		if(!is_array($this->fields[$name]['isRelationship']) || count($this->fields[$name]['isRelationship']) <= 0) {
			$modelName = get_class($this->getModel());
			return false;
		}
		
		if($returnData == true) {
			return $this->getRelationShipData($name);
		} else {
			return true;
		}
	}
	
	private function getRelationShipData($name) {
		return $this->fields[$name]['isRelationship'];
	}
	
	/**
	 * This function checks for duplicated columns in this map
	 *
	 * @param String $value
	 * @param String $index
	 * @param String $myCompare
	 */
	public function checkColumns($value,$index,$myCompare) {
		if($index == 'column' && $value == $myCompare) {
			PhpBURN_Message::output("[!Duplicated Column!]: $myCompare",PhpBURN_Message::EXCEPTION);
		}
	}
	
	/**
	 * This gets all mapinfo from a filed
	 *
	 * @param String $fieldName
	 */
	public function getField($fieldName){
		return $this->fields[$fieldName];
	}
	
	/**
	 * This gets all mapinfo from a relationship
	 *
	 * @param unknown_type $relationshipName
	 */
	public function getRelationshipInfo($relationshipName) {
		
	}
	
	/**
	 * This gets the value from the specified field
	 *
	 * @param String $field
	 */
	public function getFieldValue($field) {
		return $this->modelObj->$field = $this->fields[$field]['#value'];
	}
	
	public function getModel() {
		return $this->modelObj;
	}
	
	/**
	 * This sets the value in a field based in name, value and Mapping Info
	 *
	 * @param String $field
	 * @param unknown_type $value
	 * @access public
	 */
	public function setFieldValue($field,$value) {
		$fields = new ArrayObject($this->fields);
		$test = $fields->offsetExists($field);
		if($test === false) {
			PhpBURN_Message::output("[!This field doesn't exist in the Mapping!]: <strong>". get_class($this->modelObj) ."->$field </strong>", PhpBURN_Message::WARNING);
		}
		
		$this->fields[$field]['#value'] = $this->getModel()->$field = $value;
		$this->getFieldValue($field);
		
	}
	
	/**
	 * Validate a field based in its rules in Dialect Type
	 * @param String $fieldName
	 * @return unknown_type
	 */
	public function validateField($fieldName) {
		$keyExist = array_key_exists($fieldName, $this->fields);
		
		if($keyExist == true && $this->fields[$fieldName]['isRelationship'] == false) {
			return $this->modelObj->_dialectObj->validateValue($this->fields[$fieldName]['#value'],$this->fields[$fieldName]['type'], $this->fields[$fieldName]['length']);
		} else {
			PhpBURN_Message::output("[!This field doesn't exist or is a Relationship!]: <strong>". get_class($this->modelObj) ."->$fieldName </strong>",PhpBURN_Message::WARNING);
			return false;
		}
		
		return false;
	}
	
	/**
	 * This method removes a field from model mapping info
	 *
	 * @param String $name
	 */
	public function removeField($fieldName) {
		
	}
	
	/**
	 * Maps the model based on its XML
	 *
	 * @param PhpBURN_Core $modelObj
	 * @return SimpleXMLElement $configXML
	 */
	private function getXmlMap() {
		
		$xmlMapping = PhpBURN::loadXMLMapping($this->modelObj->_package .'.'. get_class($this->modelObj) );
		
		if($xmlMapping == 'error') {
			$xmlMapping = false;
		} else {
			$configXML = new SimpleXMLElement($xmlMapping);
		}
		
		return $configXML;
	}
}
?>