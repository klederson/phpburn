<?php

/**
 * @package PhpBURN
 * @subpackage Dialect
 * 
 * @author klederson
 *
 */
abstract class PhpBURN_Dialect implements IDialect {

  protected $obj = null;
  public $resultSet;
  public $dataSet;
  protected $pointer;

  function __construct(PhpBurn_Core $obj) {
    $this->modelObj = &$obj;
  }

  function __destruct() {
    unset($this);
  }

  public function reset() {
//            unset($this->dataSet, $this->resultSet, $this->pointer);
  }

  /**
   * EXPERIMENTAL cacheSearch
   * Looks for some condition and content into the already cached results
   * without connect the database again. Then it returns you an array of ocurrencies
   *
   * @param String $field
   * @param String $content
   * @param String $condition
   *
   * @return Mixed Numeric/False
   */
  public function cacheSearch($field, $content, $condition = '==') {
    $pointers = array();

    if (count($this->dataSet) > 0) {
      foreach ($this->dataSet as $pointer => $arrContent) {
        $strCheck = sprintf('if( "%s" %s "%s" ) { return %s; } else { return false; }', $arrContent[$field], $condition, $content, $pointer);

        $newPointer = eval($strCheck);

        if (is_numeric($newPointer)) {
          $pointers[] = $newPointer;
        }
      }
    }

    return $pointers;
  }

  public function cacheSearchRegex($field, $pattern) {
    
  }

  /**
   * Prepares and returns a dataset of resuts from the database
   *
   * @param $pk
   * @return Integer $num_rows
   */
  public function find($pk = null) {
    //Prepare the SELECT SQL Query
    $sql = $this->prepareSelect($pk);

    //Clear actual dataSet
    $this->clearDataSet();

    $modelName = get_class($this->getModel());

    if ($sql != null) {
      $this->resultSet = &$this->execute($sql);
    } else {
      PhpBURN_Message::output("[!No query found!] - <b>$modelName</b>");
      return false;
    }

    //Set cursor at the first position
    $this->setPointer(0);

    //Prepare DataSet
    //$this->setDataSet($dataSet);
    //Returns the amount result
    return $this->getModel()->getConnection()->affected_rows($this->resultSet);
  }

  /**
   * (non-PHPdoc)
   * @see app/libs/Dialect/IDialect#fetch()
   */
  public function fetch() {
    if ($this->getPointer() > $this->getLast()) {
//                $this->setPointer($this->getLast());
      $this->moveFirst();
      return false;
    } else {
      $data = $this->dataExists($this->getPointer()) ? $this->dataSet[$this->getPointer()] : $this->getModel()->getConnection()->fetch($this->resultSet);

//              Remove slashes
      foreach ($data as $index => $value) {
        $data[$index] = ($value);
      }

      if ($data != null && count($data) > 0 && !$this->dataExists($this->getPointer())) {
        $this->dataSet[$this->getPointer()] = $data;
      }

      return $data;
    }
  }

  /**
   * Verify if the data is already stored into database cache application
   *
   * @param Integer $pointer
   * @return Boolean
   */
  public function dataExists($pointer) {
    return is_array($this->dataSet[$pointer]) ? true : false;
  }

  public function getDeclaredSelectableFields() {
    if (count($this->getModel()->_select) > 0) {

      //Select based ONLY in the $obj->select(); method
      foreach ($this->getModel()->_select as $index => $value) {
        $fields .= empty($fields) ? "" : ", ";
        $fields .= $value['alias'] != null ? sprintf("%s AS %s", $value['value'], $value['alias']) : sprintf("%s", $value['value']);

        $declaredOnly = $declaredOnly != TRUE && $value['only'] != TRUE ? FALSE : TRUE;
      }

      return array(
          'declaredOnly' => $declaredOnly,
          'fields' => $fields
      );
    } else {
      return FALSE;
    }
  }

  public function prepareDelete($pk) {
    //Defnine FROM tables
    $from = 'FROM ' . $this->getModel()->_tablename;

    $whereConditions = null;

    $pkField = $this->getModel()->getMap()->getPrimaryKey();
    $pk = $pk == null ? $this->getModel()->getMap()->getFieldValue($pkField['field']['alias']) : $pk;

    $pkField = $this->getModel()->getMap()->getPrimaryKey();
    $whereConditions = $this->getWhereString($pk, $pkField);
    $whereConditions = !empty($whereConditions) ? "WHERE " . $whereConditions : $whereConditions;

    return $sql = $whereConditions == null ? null : sprintf("DELETE %s %s", $from, $whereConditions);
  }

  /**
   * Prepares the SQL Query for SELECT complete with Joins and all needed for a SELECT.
   *
   * @author Kléderson Bueno <klederson@klederson.com>
   *
   * @return String $sql
   */
  public function prepareSelect($pk = null) {
    //Globals
    $pkField = $this->getModel()->getMap()->getPrimaryKey();
    $parentFields = $this->getModel()->getMap()->getParentFields();
    $parentClass = get_parent_class($this->getModel());


    //Join the extended classes
    foreach ($parentFields as $index => $value) {
      $classVars = get_class_vars($parentClass);
      if ($parentClass == $value['classReference']) {
        $tableLeft = $this->getModel()->_tablename;
      } else {
        $tableLeft = $classVars['_tablename'];
      }

      $this->getModel()->join($value['field']['tableReference'], $value['field']['column'], $value['field']['column'], '=', 'JOIN', $tableLeft);
      unset($classVars);
    }

//    Getting declared select fields/expressions
    $declaredSelect = $this->getDeclaredSelectableFields();

//    Define declared select into SELECT expression
    $fields .= $declaredSelect != FALSE ? $declaredSelect['fields'] : '';

    //Creating the selectable fields
    if (!$declaredSelect['declaredOnly']) {
      //Selecting from the map
      foreach ($this->getModel()->getMap()->fields as $index => $value) {
        //Parsing non-relationship fields
        if (!$value['isRelationship'] && $value['field']['column'] != null) { //&& $value['classReference'] == get_class($this->getModel())) {
          $fields .= empty($fields) ? "" : ", ";
          $fields .= sprintf("%s.%s AS %s", $value['field']['tableReference'], $value['field']['column'], $index);
        }
      }
    } elseif (!$declaredSelect['declaredOnly'] && empty($fields)) {
      $model = get_class($this->modelObj);
      PhpBURN_Message::output("$model [!is not an mapped or valid PhpBURN Model!]", PhpBURN_Message::ERROR);
      exit;
    }

    $from = $this->getFromString();

    //Define Join SENTENCE
    if (count($this->getModel()->_join) > 0) {
      $joinString = $this->getJoinString();
    }

    //Define Where SENTENCE
    $whereConditions = $this->getWhereString($pk, $pkField);

    if ($whereConditions != null && isset($whereConditions) && !empty($whereConditions)) {
      $conditions = 'WHERE ';
    }

    //Define OrderBY SENTENCE
    if (count($this->getModel()->_orderBy) > 0) {
      $orderConditions = $this->getOrderByString();
    }

    //Define GroupBy SENTENCE
    if (count($this->getModel()->_groupBy) > 0) {
      $groupConditions = $this->getGroupByString();
    }

    //Define Limit SENTENCE
    if ($this->getModel()->_limit != null) {
      $limits = explode(',', $this->getModel()->_limit);
      $limit = $this->setLimit($limits[0], $limits[1]);
    }

    //Construct SQL
    $sql = $this->buildSELECTQuery($fields, $from, $joinString, $conditions, $whereConditions, $orderConditions, $groupConditions, $limit, $limits);
    unset($fieldInfo, $fields, $from, $joinString, $conditions, $whereConditions, $orderBy, $orderConditions, $limit, $pkField, $parentFields, $parentClass, $groupConditions);

    return $sql;
  }

  public function getFromString() {
//            $from = 'FROM ' . $this->getModel()->_tablename;
    if (count($this->getModel()->_from) > 0) {
      foreach ($this->getModel()->_from as $value) {
        $from .= empty($from) ? $value : sprintf(', %s', $value);
      }
    }

    $from = empty($from) ? $this->getModel()->_tablename : sprintf('%s, %s', $this->getModel()->_tablename, $from);

    return 'FROM ' . $from;
  }

  public function getWhereString($pk, $pkField) {

    if (count($this->getModel()->_where) > 0) {

      foreach ($this->getModel()->_where as $index => $value) {
        //Checking swhere and where
        if (!empty($value['mwhere'])) {
          //Normal where
//        THIS FIXES GROUPS WHEN MANUAL WHERE HAVE AND/OR CONDITION BUT HAVE NO PREDECESSOR
          if (empty($whereConditions[$value['group']]))
            $value['mwhere'] = preg_replace('(^(([ ]+)?AND|OR) )', ' ', $value['mwhere']);

          $whereConditions[$value['group']] .= ( $value['mwhere'] );
        } else {
          //SuperWhere
          $fieldInfo = $this->getModel()->getMap()->getField($value['start']);

          $value['end'] = gettype($value['end']) == "string" ? addslashes($value['end']) : $value['end'];

          $value['end'] = is_numeric($value['end']) || strpos($value['end'], 'LIKE (') !== false ? $value['end'] : sprintf("'%s'", $value['end']);
          $value['end'] = strpos($value['end'], 'LIKE (') !== FALSE ? stripslashes($value['end']) : $value['end'];

          $whereConditions[$value['group']] .= empty($whereConditions[$value['group']]) ? "" : sprintf(" %s ", $value['condition']);
          $whereConditions[$value['group']] .= sprintf(' %s.%s %s %s ', $fieldInfo['field']['tableReference'], $value['start'], $value['operator'], ($value['end']));
        }
      }
    } else {
      foreach ($this->getModel()->getMap()->fields as $field => $infos) {
        if ($this->getModel()->getMap()->getRelationShip($field) != true) {
          $value = $this->getModel()->$field;
          if (isset($value) && !empty($value) && $value != null && $value != '') {
            $fieldInfo = $this->getModel()->getMap()->getField($field);

            $value = gettype($value) == "string" ? addslashes($value) : $value;

            $value = is_numeric($value) ? $value : sprintf("'%s'", $value);

            $whereConditions[$this->getModel()->_defaultWhereGroup] .= empty($whereConditions[$this->getModel()->_defaultWhereGroup]) ? sprintf(' %s.%s %s %s ', $fieldInfo['field']['tableReference'], $fieldInfo['field']['column'], '=', ($value)) : sprintf(' AND %s.%s %s %s ', $fieldInfo['field']['tableReference'], $fieldInfo['field']['column'], '=', ($value));
          }
          unset($value);
        }
      }
    }

    if ($pk != null) {
      $pk = gettype($pk) == "string" ? addslashes($pk) : $pk;
      $pk = is_numeric($pk) ? $pk : sprintf("'%s'", $pk);

      $whereConditions[$this->getModel()->_defaultWhereGroup] .= empty($whereConditions[$this->getModel()->_defaultWhereGroup]) ? sprintf('%s.%s= %s ', $this->getModel()->_tablename, $pkField['field']['column'], $pk) : sprintf(" AND %s.%s= %s ", $this->getModel()->_tablename, $pkField['field']['column'], ($pk));
    }

    if (is_array($whereConditions)) {
      foreach ($whereConditions as $conditions) {
        $finalConditions .=!empty($finalConditions) ? ' AND ' : '';
        $finalConditions .= sprintf(' ( %s ) ', $conditions);
      }
    }

    return $finalConditions;
  }

  public function getJoinString() {

//		print_r($this->getModel()->_join);
//		exit;
//		$tableLeft, $fieldLeft = null, $fieldRight = null, $operator = '=', $joinType = 'JOIN', $tableRight = null

    foreach ($this->getModel()->_join as $index => $value) {
//			$value['tableRight'] = $value['tableRight'] == null ? $this->getModel()->_tablename : $value['tableRight'];
      $joinString .= $joinString != null ? ' ' : null;
      $joinString .= sprintf('%s %s', $value['type'], $index);
      if ($value['fieldLeft'] != null && $value['fieldRight'] != null) {

        $rightSide = $value['tableRight'] == null ? sprintf('"%s"', $value['fieldRight']) : sprintf('%s.%s', $value['tableRight'], $value['fieldRight']);

        $joinString .= sprintf(" ON %s.%s %s %s", $value['tableLeft'], ($value['fieldLeft']), $value['operator'], $rightSide);
      }
    }

    return $joinString;
  }

  public function getOrderByString() {

    $orderBy = 'ORDER BY ';
    foreach ($this->getModel()->_orderBy as $index => $value) {
      $fieldInfo = $this->getModel()->getMap()->getField($value['field']);
      $orderConditions .= $orderConditions == null ? "" : ", ";
      $orderConditions .= $fieldInfo['field']['tableReference'] . '.' . $fieldInfo['field']['column'] . ' ' . $value['type'];
    }


    return $orderBy . $orderConditions;
  }

  public function getGroupByString() {

    $clause = 'GROUP BY ';
    foreach ($this->getModel()->_groupBy as $index => $value) {
      $fieldInfo = $this->getModel()->getMap()->getField($value['field']);
      $conditions .= $conditions == null ? "" : ", ";
      $conditions .= $fieldInfo['field']['tableReference'] . '.' . $fieldInfo['field']['column'];
    }


    return $clause . $conditions;
  }

  /* Execution */

  /**
   * Calls the Connection Object and perform a SQL QUERY into the Database
   *
   * @param String $sql
   */
  public function execute($sql) {
    PhpBURN_Message::output("[!Performing the query!]: $sql");
    return $this->getModel()->getConnection()->executeSQL($sql);
    //$this->resultSet = &$this->getModel()->getConnection()->executeSQL($sql);
  }

  /**
   * Calls the Connection Object and perform a SQL QUERY into the Database
   *
   * @param String $sql
   */
  public function executeUnbuff($sql) {
    PhpBURN_Message::output("[!Performing the query!]: $sql");
    return $this->getModel()->getConnection()->unbuffExecuteSQL($sql);
    //$this->resultSet = &$this->getModel()->getConnection()->executeSQL($sql);
  }

  public function hasInherit($class) {
    if ($class instanceof PhpBURN_Core) {
      return false;
    } else {
      return get_parent_class($class);
    }
  }

  public function save() {
    $isInsert = true;

    //Verify if the PK value has been set
    $pkField = $this->getMap()->getPrimaryKey();
    if (isset($this->getModel()->$pkField['field']['alias']) && !empty($this->getModel()->$pkField['field']['alias'])) {
      $isInsert = false;
    }

//		Verify if exists and update trought inhirit
//		if($this->hasInherit($this) != false && ) {
//			
//		}
    //Preparing the SQL
    $sql = $isInsert == true ? $this->prepareInsert() : $this->prepareUpdate();

    $sql = array_reverse($sql, true);

//		Checks how many sqls have been generated ( based on extended classes )
    if (count($sql) > 0) {
      foreach ($sql as $index => $value) {
        if ($isInsert == true) {
          $parentClass = $this->getModel()->getMap()->getTableParentClass($index);
          $lastId = $this->getModel()->getConnection()->last_id();
          $parentField = $this->getModel()->getMap()->getClassParentField($parentClass);
          if (count($parentField) > 0) {
            $this->getMap()->setFieldValue($parentField['field']['alias'], $lastId);
          }
          $parentColumn = $parentField['field']['column'] != null && !empty($parentField['field']['column']) ? sprintf(', %s', $parentField['field']['column']) : '';
          $parentValue = $parentField['field']['column'] != null && !empty($parentField['field']['column']) ? sprintf(", '%s'", $lastId) : '';
          $value = str_replace('[#__fieldLink#]', $parentColumn, $value);
          $value = str_replace('[#__fieldLinkValue#]', $parentValue, $value);
        }
        if ($value != null)
          if(!$this->execute($value)) return FALSE; //handle errors when saving
      }
      //$this->getModel()->get($this->getModel()->getConnection()->last_id());
      $field = $this->getMap()->getPrimaryKey();
      $lastId = $this->getModel()->getConnection()->last_id();
      if ($isInsert == true) {
        $this->getMap()->setFieldValue($field['field']['alias'], $lastId);
      }

      $this->saveRelationships();

      return true;
    } else {
      return false;
    }
  }
  
  public function saveRelationships($name = NULL) {
    if($name == NULL) {
      foreach ($this->getMap()->fields as $fieldCheck => $infos) {
        if ($this->getModel()->getMap()->getRelationShip($fieldCheck) == true && $this->getModel()->$fieldCheck instanceof $infos['isRelationship']['foreignClass']) {
          $this->saveRelationship($infos, $fieldCheck);
        }
      }
    } else {
      $infos = $this->getMap()->fields[$name];
      $this->saveRelationship($infos, $name);
    }
  }

  protected function saveRelationship(&$infos, &$fieldCheck) {

//					Just to short name
    $relModel = &$this->getModel()->$fieldCheck;

//					Checking the kind of relationship
    switch ($infos['isRelationship']['type']) {
      case PhpBURN_Core::ONE_TO_ONE:
        $this->getModel()->$fieldCheck->save();
        $this->getModel()->getMap()->setFieldValue($infos['isRelationship']['thisKey'], $relModel->getMap()->getFieldValue($infos['isRelationship']['thisKey']));
        $this->getModel()->save();
        break;

      case PhpBURN_Core::ONE_TO_MANY:
        $relModel->getMap()->setFieldValue($infos['isRelationship']['relKey'], $this->getModel()->getMap()->getFieldValue($infos['isRelationship']['relKey']));
        $this->getModel()->$fieldCheck->save();
        break;

      case PhpBURN_Core::MANY_TO_MANY:
        $this->getModel()->$fieldCheck->save();

//			SEARCH IF THE RELATIONSHIP ALREADY EXISTS
        unset($sqlWHERE, $relationshipSQL, $rs);
        $relKeyVal = $this->getModel()->getMap()->getFieldValue($infos['isRelationship']['relKey']);
        $relOutKeyVal = $relModel->getMap()->getFieldValue($infos['isRelationship']['relOutKey']);

        $sqlWHERE = sprintf("%s.%s = '%s'", $infos['isRelationship']['relTable'], $infos['isRelationship']['relKey'], addslashes($relKeyVal));
        $sqlWHERE .= " AND ";
        $sqlWHERE .= sprintf("%s.%s = '%s'", $infos['isRelationship']['relTable'], $infos['isRelationship']['outKey'], addslashes($relOutKeyVal));

        $relationshipSQL = sprintf('SELECT * FROM %s WHERE %s', $infos['isRelationship']['relTable'], $sqlWHERE);

        $rs = $this->execute($relationshipSQL);
        if ($this->getModel()->getConnection()->affected_rows() == 0) {
          unset($sqlWHERE, $relationshipSQL, $rs);
          $relationshipSQL = sprintf("INSERT INTO %s ( %s, %s ) VALUES ( '%s' , '%s' ) ", $infos['isRelationship']['relTable'], $infos['isRelationship']['relKey'], $infos['isRelationship']['relOutKey'], $relKeyVal, $relOutKeyVal);
          $rs = $this->execute($relationshipSQL);
        } else if ($this->getModel()->getConnection()->affected_rows() > 0 && class_exists($infos['isRelationship']['relTable'])) {
          $relModel = new $infos['isRelationship']['relTable'];

          $relModel->$infos['isRelationship']['relKey'] = $relKeyVal;
          $relModel->$infos['isRelationship']['relOutKey'] = $relOutKeyVal;

          if ($relModel->find() > 0) {
            $relModel->fetch();

            foreach ($relModel->toArray() as $relFieldName => $value) {
              $_name = sprintf('_rel_%s', $relFieldName);
              if (isset($this->getModel()->$_name))
                $relModel->$relFieldName = $this->getModel()->$_name;
            }

            $relModel->save();
          }
        }

//              @TODO maybe this is a nice place to put save relationship data to reltable
        break;
    }
  }

  public function prepareInsert() {
    //Globals
    $pkField = $this->getModel()->getMap()->getPrimaryKey();
    $parentFields = $this->getModel()->getMap()->getParentFields();
    $parentClass = get_parent_class($this->getModel());


    //Join the extended classes
    foreach ($parentFields as $index => $value) {
      $classVars = get_class_vars($parentClass);
      $this->getModel()->join($classVars['_tablename'], $pkField['field']['column'], $value['field']['column'], '=');
      unset($classVars);
    }

    foreach ($this->getModel()->getMap()->fields as $field => $infos) {
      if ($this->getModel()->getMap()->getRelationShip($field) != true) {

        $this->getModel()->getMap()->setFieldValue($field, $this->getModel()->$field);
        $value = $this->getModel()->getMap()->getFieldValue($field);
        $this->getMap()->fetchFieldValue($field, $this->getModel()->$field);

        if (isset($value) && $value != null) {
          $insertFields[$infos['field']['tableReference']] .= $insertFields[$infos['field']['tableReference']] == null ? '' : ', ';
          $insertFields[$infos['field']['tableReference']] .= $infos['field']['tableReference'] . '.' . $infos['field']['column'];
          $insertValues[$infos['field']['tableReference']] .= $insertValues[$infos['field']['tableReference']] == null ? '' : ', ';
          $insertValues[$infos['field']['tableReference']] .= sprintf("'%s'", addslashes($value));
        }
      } else if ($this->getModel()->getMap()->getRelationShip($field) == true && !empty($this->getModel()->$field)) {
        
      }
    }

    //Define sqls based on each table from the parent to the child
    foreach ($insertFields as $index => $insertFieldsUnique) {
      $sql[$index] = sprintf("INSERT INTO %s ( %s [#__fieldLink#] ) VALUES ( %s [#__fieldLinkValue#] ) ", $index, $insertFieldsUnique, $insertValues[$index]);
    }

    //Pre-defined parms
    $tableName = &$this->getModel()->_tablename;

    //Constructing the SQL
    return $sql;
  }

  public function prepareUpdate() {
    //Searching for compound PKs or all Pks ( including parent and childs ones )
    $pkFields = &$this->getMap()->getPrimaryKey(FALSE);

    $updatedFields = null;
    //Checking each MAPPED field looking in cache for changes in field value, if existis it will be updated, if not we just update the right fields
    foreach ($this->getMap()->fields as $field => $infos) {
      if ($this->getModel()->getMap()->getRelationShip($field) != true && ( isset($this->getModel()->getMap()->fields[$infos['field']['alias']]) && $this->getModel()->$infos['field']['alias'] != $infos['#fetch_value'] )) {
        $this->getMap()->setFieldValue($field, $this->getModel()->$field);
        $this->getMap()->fetchFieldValue($field, $this->getModel()->$field);
        $updatedFields[$infos['field']['tableReference']] .= $updatedFields[$infos['field']['tableReference']] == null ? '' : ', ';
        $updatedFields[$infos['field']['tableReference']] .= sprintf("%s='%s'", $infos['field']['column'], addslashes($this->getModel()->$field));

//      Prepare the wehere for one or many pk fields
        if (is_array($pkFields)) {
          foreach ($pkFields as $pkFname => $pkArray) {
            $pkWhere[$pkArray['field']['tableReference']] .=!empty($pkWhere[$pkArray['field']['tableReference']]) ? "AND" : "";
            $pkWhere[$pkArray['field']['tableReference']] .= sprintf(" %s = '%s'", $pkFname, $pkArray['#fetch_value']);
          }
        }
      }
    }

    //Define sqls based on each table from the parent to the child
    if (count($updatedFields) > 0) {
      foreach ($updatedFields as $index => $updatedFieldsUnique) {
//        $pkField = $index == $this->getModel()->_tablename ? $this->getMap()->getPrimaryKey() : $this->getMap()->getTableParentField($index);

        $sql[$index] = $updatedFields != null ? sprintf("UPDATE %s SET %s WHERE %s;", $index, $updatedFieldsUnique, $pkWhere[$index]) : null;
      }
    }

    //Constructing the SQL
    //$sql = $updatedFields != null ? sprintf("UPDATE %s SET %s WHERE %s='%s'", $tableName, $updatedFields, $pkField['field']['column'], $pkField['#value']) : null;

    $modelName = get_class($this->getModel());
    if ($sql == null) {
      PhpBURN_Message::output("[!There is nothing to save in model!]: <b>$modelName</b>", PhpBURN_Message::WARNING);
      return array(null);
    } else {
      return $sql;
    }
    //$this->execute($sql);
  }

  public function delete($pk = null) {
//		Getting the DELETE QUERY
    $sql = $this->prepareDelete($pk);

    if ($sql != null) {
      return $this->execute($sql);
    } else {
      $modelName = get_class($this->getModel());
      PhpBURN_Message::output("[!Nothing to delete!] - <b>$modelName</b>");
      return false;
    }
  }

  /* Treatment */

  /* Internals */

  /**
   * (non-PHPdoc)
   * @see app/libs/Dialect/IDialect#setConnection()
   */
  public function setConnection($connection) {
    $this->connection = &$connection;
  }

  /**
   * (non-PHPdoc)
   * @see app/libs/Dialect/IDialect#getConnection()
   */
  public function getConnection() {
    return $this->getModel()->getConnection();
  }

  public function getMap() {
    return $this->getModel()->getMap();
  }

  public function getModel() {
    return $this->modelObj;
  }

  /* DataSet access */

  public function setDataSet(array $dataSet) {
    $this->dataSet = $dataSet;
  }

  public function getDataSet() {
    return $this->dataSet;
  }

  public function clearDataSet() {
    unset($this->dataSet);
  }

  /* Navigational Methods */

  /**
   * (non-PHPdoc)
   * @see app/libs/Dialect/IDialect#moveNext()
   */
  public function moveNext() {
    if ($this->getPointer() <= $this->getLast()) {
      $this->pointer++;

      return $this->pointer;
    } else {
      return false;
    }
  }

  /**
   * (non-PHPdoc)
   * @see app/libs/Dialect/IDialect#movePrev()
   */
  public function movePrev() {
    if ($this->pointer > 0) {
      $this->pointer--;

      return $this->pointer;
    } else {
      return false;
    }
  }

  /**
   * (non-PHPdoc)
   * @see app/libs/Dialect/IDialect#moveFirst()
   */
  public function moveFirst() {
    return $this->pointer = 0;
  }

  /**
   * (non-PHPdoc)
   * @see app/libs/Dialect/IDialect#moveLast()
   */
  public function moveLast() {
    return $this->pointer = $this->getLast();
  }

  /**
   * (non-PHPdoc)
   * @see app/libs/Dialect/IDialect#getLast()
   */
  public function getLast() {
    return $this->getAmount() - 1;
  }

  public function getAmount() {
    return $this->getConnection()->num_rows($this->resultSet);
  }

  /**
   * (non-PHPdoc)
   * @see app/libs/Dialect/IDialect#getPointer()
   */
  public function getPointer() {
    return $this->pointer;
  }

  /**
   * (non-PHPdoc)
   * @see app/libs/Dialect/IDialect#setPointer()
   */
  public function setPointer($pointer) {
    $this->pointer = $pointer;
  }

}

?>