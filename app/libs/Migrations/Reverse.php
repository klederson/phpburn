<?php
class PhpBURN_Reverse {

    public static $fks, $rawFks, $rawFields, $fields, $thisTableFks;

    private static $thisPath;

    public function init() {
        print self::$thisPath = realpath(dirname(__FILE__));
        print "<pre>";
        
        $packages = PhpBURN_Configuration::getConfig();

        foreach($packages as $package => $packageConfig) {
           
            $class = new PhpBURN_ReverseClass('null',$packageConfig->package);

            $tables = $class->getConnection()->getTables();

            foreach($tables as $table) {

                $fieldData = $class->getConnection()->describe($table);

                foreach($fieldData as $data) {
                    $field['name'] = $data[0];
                    $field['type'] = $data[2];
                    $field['lenght'] = $data[3];

                        $options['primary'] = $data[4];
                        $options['not_null'] = $data[5];
                        $options['default_value'] = $data[6];
                        $options['auto_increment'] = $data[7];
                    
                    $field['options'] = $options;
                    $field['package'] = $packageConfig->package;
                    $field['tableName'] = $table;

                    self::$rawFields[$packageConfig->database . '.' . $table][] = $field;
                    self::$fields[$packageConfig->database . '.' . $table][] = self::generateField($data[0], $data[2], $data[3], $options);
                
                    unset($field);
                }

//                self::$fields[$packageConfig->database . '.' . $table] = $fields;

                $fks = !is_array($fks) ? array() : $fks;
                $tableFks = $class->getConnection()->getForeignKeys($table); //IS NOT MODEL FK IS TABLE



                foreach($tableFks as $index => $keyArr) {
                    self::$rawFks[$index][] = $keyArr;

                    self::$thisTableFks[$keyArr['references']][] = $keyArr;
                }

    //          post-reverse
                unset($fields, $tableFks, $fieldData);


            }
        }
//
//        print "<h1> ===== FINAL FIELDS ===== </h1>";
//        print_r(self::$fields);
//
//        print "<h1> ===== RAW FKS ===== </h1>";
//        print_r(self::$rawFks);


        foreach(self::$thisTableFks as $origin => $item) {
            foreach($item as $index => $value) {
                self::$fks[$value['toDatabase'].'.'.$value['referencedTable']][] = self::generateRelationship($origin, $value);
            }
        }
//
//        print "<h1> ===== FINAL EXAMPLE ===== </h1>";
//        print_r(self::$thisTableFks);
//
//        print "<h1> ===== FINAL FKS ===== </h1>";
//        print_r(self::$fks);

//        self::constructModelFiles();
    }
    
    public function generateRelationship($origin, array $value) {
        $isManyToMany = preg_match_all("(([A-z0-9_]+)_has_([a-z,A-Z,0-9,_]+))", $value['thisTable'], $checkMany);

        $thisKey = $value['referencedColumn'];
        $relKey = $value['thisColumn'];
        $relTable = $value['thisTable'];
        $outKey = null;
        $relOutKey = null;

        if($isManyToMany && $value['referencedTable'] == $checkMany[1][0]) {
            $relType = 'self::MANY_TO_MANY';
            $name = $foreignClass = $checkMany[2][0];

            $keys = array_keys(self::$rawFks);

            $outKey = 'a';
            $relOutKey = 'b';
            
            $relTemplate = sprintf('$this->getMap()->addRelationship("%s", %s, "%s", "%s", "%s", "%s", "%s", "%s");',$foreignClass, $relType, $foreignClass, $thisKey, $relKey, $outKey, $relOutKey, $relTable);
        } else if($isManyToMany == 0) {
            $relType = 'self::ONE_TO_MANY';
            $name = $foreignClass = $value['thisTable'];
            
            $relTemplate = sprintf('$this->getMap()->addRelationship("%s", %s, "%s", "%s", "%s");',$name, $relType, $foreignClass, $thisKey, $relKey);
        }

        unset($isManyToMany, $checkMany);

        return $relTemplate;
    }

    public function generateField($name, $type, $lenght, array $options ) {

        foreach($options as $index => $value) {
            $value = $value == null ? null : $value;
            $value = is_string($value) ? sprintf("'%s'", $value) : $value;

            if($value != null) {
                $optionStr .= !empty($optionStr) ? ", " : "";
                $optionStr .= sprintf('"%s" => %s', $index, $value);
            }
        }

        $lenght = $lenght != null ? $lenght : 'null';
        return sprintf('$this->getMap()->addField( "%s","%s", "%s", %s, array(%s) );', $name, $name, $type, $lenght, $optionStr);

    }

    public function constructModelFiles($package = null, $tableName = null) {
//        $modelTemplate = PhpBURN::loadFile();

//        PhpBURN_Views::setViewMethod('phptal');
        
        foreach(self::$rawFields as $fullName => $arrContent) {
            preg_match_all("((([a-z,A-Z,0-9,_]+)\.)?([a-z,A-Z,0-9\.,_]+))", $fullName, $separation);

            $viewData['package'] = $separation[2][0];
            $viewData['tableName'] = $separation[3][0];
            $viewData['rawFields'] = $arrContent;
            $viewData['fields'] = self::$fields[$fullName];
            $viewData['rawFks'] = self::$rawFks[$fullName];
            $viewData['fks'] = self::$fks[$fullName];

            $content = "<?php\r\n";
            $content .= PhpBURN_Views::loadViewFile(self::$thisPath . DS . 'modelTemplate.html',$viewData, true);
            $content .= "\r\n?>\r\n";

            if(!is_dir(SYS_MODEL_PATH . $viewData['package'])) {
                SYS_MODEL_PATH . $viewData['package'];
                mkdir(SYS_MODEL_PATH . $viewData['package'],0777);
            }

            $fileName = sprintf("%s%s",$viewData['tableName'],SYS_MODEL_EXT);
            $filePath = SYS_MODEL_PATH . $viewData['package'];
            $file = fopen( $filePath . DS . $fileName ,'w+');
            fwrite($file, $content);
            fclose($file);

            unset($content);
        }

        
    }
}

class PhpBURN_ReverseClass extends PhpBURN_Core {
    public $_tablename = null;
    public $_package = null;

    public function _mapping() {
        
    }
}
?>