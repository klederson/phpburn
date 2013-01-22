<?php
PhpBURN::load('Mapping.Map');

/**
 * This class manage the map controller. 
 * It is the main responsable for create and delegate the maps into the models.
 * Can be used by many ways but by default its better if you simply do not mess around ;) let the application work for you.
 * 
 * @package PhpBURN
 * @subpackage Mapping
 * 
 * @author Kléderson Bueno <klederson@klederson.com>
 * @version 0.1a
 */
class PhpBURN_Mapping
{
	/**
	 * This variable storage in runtime all maps for each kind of model. For more details 
	 * @see getMapping()
	 * @var Array
	 */
	public static $mapping = array();
	
	/**
	 * Creates and return a PhpBURN_Map Object for the calling model
	 * If the map already exists it just return it ( caching )
	 *
	 * @param PhpBURN_Core $modelObj
	 * @return PhpBURN_Map
	 */
	public static function create(PhpBURN_Core &$modelObj,$fromMulti = false) {		
		$mapObj = self::getMapping(get_class($modelObj));
		
		/*
		 * @TODO Para os multimaps/heranças basta adicionar o campo com o nome da classe pertencente no parentMap assim o o mapa ficará completo e o campo saberá a quem pertence e poderá ser salvo na tabela
		 */
		
		if($mapObj == null) {
			
			$mapObj = new PhpBURN_Map($modelObj);
			self::addMap($modelObj,$mapObj);
			
			/*
			if(count($parentMaps) > 0 && $fromMulti != false) {	
				//Prepare multi-map item
				$modelObj->_multiMap = true;
				$mapObj = $this->addMultiMap($parentMaps,$modelObj,$mapObj);
			}
			*/
			
			//Here we just set our already cloned $mapObj
			$modelObj->_mapObj = $mapObj;
			
			//Makes the mapping into the object
			//NOTE This must to be here because the _mapping() method into the Model
			$modelObj->_mapObj->mapThis();
			
			//Check for parentMaps ( inhirit )
			$parentMaps = self::cascadeMaps($modelObj);
			
			//Here we clone because it is the first model, we cant use the base object as map because we can have troubles with references and stored data
			$mapObj = clone $mapObj;
			
		} else {
			$mapObj = clone $mapObj;
			$mapObj->modelObj = $modelObj;
			$mapObj->reset();		
			
			//Here we just set our already cloned $mapObj
			$modelObj->_mapObj = $mapObj;
		}
		
		return $mapObj;
	}
	
	/**
	 * It verify if the model is a Child of other model
	 * 
	 * @param PhpBURN_Core $modelObj
	 * @return Boolean
	 */
	private static function isChild(PhpBURN_Core &$modelObj) {
		if(get_parent_class($modelObj) != 'PhpBURN_Core' && empty($modelObj->_tablename)) {
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * Add a new map to mapList
	 *
	 * @param PhpBURN_Core $modelObj
	 * @param PhpBURN_Map $mapObj
	 */
	public static function addMap(PhpBURN_Core &$modelObj,PhpBURN_Map &$mapObj) {
		self::$mapping[get_class($modelObj)] = $mapObj;
	}
	
	/**
	 * This method add to our default method external maps and infos from inhirited models
	 *
	 * @param PhpBURN_Map $parentMaps
	 * @param PhpBURN_Core $modelObj
	 * @param PhpBURN_Map $mapObj
	 */
	public static function addMultiMap($parentMaps,PhpBURN_Core &$modelObj,PhpBURN_Map &$mapObj) {
		
	}
	
	/**
	 * Get the correspondent map for the Model
	 *
	 * @param PhpBURN_Core $modelObj
	 * @return PhpBURN_Map
	 */
	public static function getMapping($className) {
		if(self::$mapping[$className] != null && self::$mapping[$className] != '') {
			return self::$mapping[$className];
		} else {
			return null;
		}
	}
	
	/**
	 * Checks if the object is a child from another PhpBURN_Core Object(s)
	 *
	 * @param String $class
	 * @return PhpBURN_MappingItem
	 */
	public static function cascadeMaps(PhpBURN_Core &$modelObj) {
		if(self::isChild($modelObj)) {
			$class = get_parent_class($modelObj);
			$_parentMap = self::getMapping($class);
			if($_parentMap == null) {
				$_tmpModelObj = new $class;
				$_parentMap = $_tmpModelObj->_mapObj;
			}
			$clonedFields = $_parentMap->cloneAttributes();
			$clonedReferences = $_parentMap->cloneReferences();
			
			//Put the attributes into this current map as incremental mode
			$modelObj->_mapObj->setAttributes($clonedFields);
			$modelObj->_mapObj->setReferences($clonedReferences);
			
			unset($class, $cloned, $_tmpModelObj, $_parentMap);
		}
	}
}
?>