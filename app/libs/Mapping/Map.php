<?php
PhpBURN::load('Mapping.IMap');

class PhpBURN_Map implements IMap {
	
	//Relationship types
	const ONE_TO_ONE 						= 100101;
	const ONE_TO_MANY 						= 100102;
	const MANY_TO_MANY 					= 100103;
	
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
	 * 									***['parentReferences'] => ( parentPackage, class, table, column )
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
	public function addRelationship($relName,$relType,$foreignClass, $thisKey, $relKey, $outKey, $relOutKey, $relTable, $lazy) {

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
			$this->fields[$relName]['isRelationship']['relTabl'] = $relTable;
			$this->fields[$relName]['isRelationship']['lazy'] = $lazy;
		
		//For child relationships ONLY
		$this->fields[$relName]['isExternal'] = false;
		
		//For multipMap ONLY
		$this->fields[$relName]['parentReferences'] = get_class($this->modelObj);
		
		//Setup defaultvalue for this field
		$this->setFieldValue($relName,null);
	}
	
	public function addParentRelationShip() {
		
	}
	
	public function addParentField($name, $column, $type, $length, array $options, $parentClass) {
		
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
		//Check for duplicated columns in this map
		array_walk_recursive($this->fields,array($this, 'checkColumns'),$name);
		
		//Setup a simple field		
		$this->fields[$name]['field']['type'] = $type;
		$this->fields[$name]['field']['alias'] = $name;
		$this->fields[$name]['field']['column'] = $column;
		$this->fields[$name]['field']['type'] = $type;
		$this->fields[$name]['field']['length'] = $length;
		$this->fields[$name]['field']['options'] = count($options) > 0 ? $options : array();
		
		//Just for double check it sets false to other kinds of field
		$this->fields[$name]['isRelationship'] = false;
		
		//When it belongs to a child class
		$this->fields[$name]['isExternal'] = false;
		
		//For multipMap use ONLY
		$this->fields[$name]['parentReferences'] = get_class($this->modelObj);
		
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
		//TODO Send an Exeption Message: "[!There is no Primary Key for <b>$modelName</b> model. How did you did it?!]";
		print "[!There is no Primary Key for <b>$modelName</b> model. How did you did it?!]";
		exit;
	}
	
	public function getRelationShip($name, $returnData = false) {
		if(!is_array($this->fields[$name]['isRelationship']) || count($this->fields[$name]['isRelationship']) <= 0) {
			$modelName = get_class($this->getModel());
			//TODO Send an Warning Message: "[!There is no such relationship for <b>$modelName</b> model. Are you sure you're looking for <b>'$name'</b>?!]";
			//print "[!There is no such relationship for <b>$modelName</b> model. Are you sure you're looking for <b>'$name'</b>?!]";
			return false;
			//exit;
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
			//TODO Send an Exeption Message: [!Duplicated Column!]: $myCompare
			print "[!Duplicated column!]: $myCompare";
			exit;
		}
	}
	
	/**
	 * This gets all mapinfo from a filed
	 *
	 * @param String $fieldName
	 */
	public function getFieldInfo($fieldName){
		
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
	 * This sets the value in a field based in name, value and mappinginfo
	 *
	 * @param String $field
	 * @param unknown_type $value
	 */
	public function setFieldValue($field,$value) {
		$fields = new ArrayObject($this->fields);
		$test = $fields->offsetExists($field);
		if($test === false) {
			//TODO Send an Warning Message:"[!Warning!] : [!This field doesn't exist in the Mapping!]: <strong>". get_class($this->modelObj) ."->$field </strong>";
			print "[!Warning!]: [!This field doesn't exist in the Mapping!]: <strong>". get_class($this->modelObj) ."->$field </strong>";
		}
		
		$this->fields[$field]['#value'] = $value;
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
			//TODO Send an Exception Message: "[!This field doesn't exist or is a Relationship!]: <strong>". get_class($this->modelObj) ."->$fieldName </strong>"
			print "[!This field doesn't exist or is a Relationship!]: <strong>". get_class($this->modelObj) ."->$fieldName </strong>";
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