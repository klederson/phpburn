<?php
PhpBURN::load('Mapping.Map');

class PhpBURN_Mapping
{
	private static $mapping = array();
	
	/**
	 * Creates and return a PhpBURN_Map Object for the calling model
	 * If the map already exists it just return it ( caching )
	 *
	 * @param PhpBURN_Core $modelObj
	 * @return unknown
	 */
	public function create(PhpBURN_Core $modelObj,$fromMulti = false) {		
		$mapObj = $this->getMapping(get_class($modelObj));
		
		if($mapObj == null) {
			
			$mapObj = new PhpBURN_Map($modelObj);
			$this->addMap($modelObj,$mapObj);
			
			//Check for parentMaps ( inhirit )
			$parentMaps = $this->cascadeMaps(get_class($modelObj));
			
			if(count($parentMaps) > 0 && $fromMulti != false) {	
				//Prepare multi-map item
				$modelObj->_multiMap = true;
				$mapObj = $this->addMultiMap($parentMaps,$modelObj,$mapObj);
			}
			
			//Here we clone because it is the first model, we cant use the base object as map because we can have troubles with references and stored data
			$mapObj = clone $mapObj;
			
			//Here we just set our already cloned $mapObj
			$modelObj->_mapObj = $mapObj;
			
			//Makes the mapping into the object
			//NOTE This must to be here because the _mapping() method into the Model
			$modelObj->_mapObj->mapThis();
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
	 * Add a new map to mapList
	 *
	 * @param PhpBURN_Core $modelObj
	 * @param PhpBURN_Map $mapObj
	 */
	public function addMap(PhpBURN_Core $modelObj,PhpBURN_Map $mapObj) {
		self::$mapping[get_class($modelObj)] = $mapObj;
	}
	
	/**
	 * This method add to our default method external maps and infos from inhirited models
	 *
	 * @param PhpBURN_Map $parentMaps
	 * @param PhpBURN_Core $modelObj
	 * @param PhpBURN_Map $mapObj
	 */
	public function addMultiMap($parentMaps,PhpBURN_Core $modelObj,PhpBURN_Map $mapObj) {
		
	}
	
	/**
	 * Get the correspondent map for the Model
	 *
	 * @param PhpBURN_Core $modelObj
	 * @return PhpBURN_Map
	 */
	public function getMapping($className) {
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
	public function cascadeMaps($class) {
		while($class = get_parent_class($class)) { 
			if($class != "PhpBURN_Core") {
				$_class = new $class;
				$maps[] = $this->create($_class);
				unset($_class);
			}
		}
		return $maps;
	}
}
?>