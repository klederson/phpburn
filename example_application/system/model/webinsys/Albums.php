<?php
class Albums extends PhpBURN_Core {
	public $_package = 'webinsys';
	public $_tablename = 'albums';
	
	public $id_album;
	public $name;
	public $created_at;
	
	/**
	 * Here we setup all mapping fields without user XML
	 * 
	 * IMPORTANT: This method will ONLY be called automaticaly if the model DOES NOT have a xml Map
	 * 
	 * @example $this->_mapObj->addField('name','column','sqlType','length',array('notnull' => true, 'autoincrement' => true);
	 * @example $this->_mapObj->addField('name','column','sqlType','length',array();
	 * @example $this->_mapObj->addField('id','user_id','int','10',array('notnull' => true, 'pk' => true, 'autoincrement' => true);
	 */
	public function _mapping() {
		$this->getMap()->addField('id_album', 'id_album', 'int', '10',array('primary' => true, 'autoincrement' => true, 'notnull' => true));
		$this->getMap()->addField('name', 'name', 'varchar', '255',array('notnull' => true));
		$this->getMap()->addField('created_at', 'created_at', 'timestamp', null ,array('notnull' => true, 'defaultvalue' => 'CURRENT_TIMESTAMP'));
		
		// addRelationship($relName,$relType,$foreignClass, $thisKey, $relKey, $outKey, $relOutKey, $relTable, $lazy) ;
		$this->_mapObj->addRelationship('pictures',parent::ONE_TO_MANY,'Pictures', 'id_album', 'id_album',null, null, null, false);
		$this->_mapObj->addRelationship('tags',parent::MANY_TO_MANY,'Tags', 'id_album', 'id_album','id_tags', 'id_tags', 'rel_album_tags', false);
	}
}
?>