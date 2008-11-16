<?php
/**
 * This interface is responsable for the individual Mapping for each Model
 */
interface IMap {

	public function __construct(PhpBURN_Core &$model);
	
	/**
	 * This method gets all mapinfo from a filed
	 *
	 * @param String $fieldName
	 */
	public function getFieldInfo($fieldName);
	
	/**
	 * This method gets all mapinfo from a relationship
	 *
	 * @param unknown_type $relationshipName
	 */
	public function getRelationshipInfo($relationshipName);
	
	/**
	 * This method gets the value from the specified field
	 *
	 * @param String $field
	 */
	public function getFieldValue($field);
	
	/**
	 * This method sets the value in a field based in name, value and mappinginfo
	 *
	 * @param String $field
	 * @param unknown_type $value
	 */
	public function setFieldValue($field,$value);
	
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
	public function addField($name, $column, $type, $length, array $options);
	
	/**
	 * This method removes a field from model mapping info
	 *
	 * @param String $name
	 */
	public function removeField($name);
	
	
}
?>