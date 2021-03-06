<?php
class PhpBURN_Reverse {

    public static $fks, $rawFks, $rawFields, $fields, $thisTableFks;

    private static $thisPath;

    public function init() {
        self::$thisPath = realpath(dirname(__FILE__));
        $fks = null;
        
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

                    self::$thisTableFks[$keyArr['referencedTable']][] = $keyArr;
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
                self::$fks[$value['referencedTable']][] = self::generateRelationship($origin, $value);
            }
        }
//
//        print "<h1> ===== FINAL EXAMPLE ===== </h1>";
//        print_r(self::$thisTableFks);
//
//        print "<h1> ===== FINAL FKS ===== </h1>";
//        print_r(self::$fks);

        self::constructModelFiles();
    }
    
    public function generateRelationship($origin, array $value) {
        $isManyToMany = preg_match_all("(([A-z0-9_]+)_has_([a-z,A-Z,0-9,_]+))", $value['thisTable'], $checkMany);

        $thisKey = $value['referencedColumn'];
        $relKey = $value['thisColumn'];
        $relTable = $value['thisTable'];
        $outKey = null;
        $relOutKey = null;
        $relTemplate = null;

        if($isManyToMany && ( strtolower($value['referencedTable']) == strtolower($checkMany[1][0])) ) {
            $relType = 'self::MANY_TO_MANY';
            $name = $foreignClass = $checkMany[2][0];
//            print "<h1>$foreignClass</h1>";
//            print_r($value);
//            print_r($checkMany);

            $outArr = self::$rawFks[($checkMany[2][0])];

            if(is_array($outArr)) {
                foreach($outArr as $index => $arrValue) {
                    if($checkMany[0][0] == $arrValue['thisTable']) {
                        $outKey = $arrValue['referencedColumn'];
                        $relOutKey = $arrValue['thisColumn'];
                    }
                }
            }

            $keys = array_keys(self::$rawFks);
            
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
        $optionStr = null;
        
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

        if(is_writable(SYS_MODEL_PATH)) {

            foreach(self::$rawFields as $fullName => $arrContent) {
                preg_match_all("((([a-z,A-Z,0-9,_]+)\.)?([a-z,A-Z,0-9\.,_]+))", $fullName, $separation);

                PhpBURN_Views::setViewMethod('default');

                $viewData['package'] = $separation[2][0];
                $viewData['tableName'] = $separation[3][0];
                $viewData['className'] = ucwords(str_replace('.','_',$separation[3][0]));
                $viewData['rawFields'] = $arrContent;
                $viewData['fields'] = self::$fields[$fullName];
                $viewData['rawFks'] = isset(self::$rawFks[strtolower($separation[3][0])]) ? self::$rawFks[strtolower($separation[3][0])] : self::$rawFks[($separation[3][0])];
                $viewData['fks'] = isset(self::$fks[strtolower($separation[3][0])]) ? self::$fks[strtolower($separation[3][0])] : self::$fks[($separation[3][0])];

                $content = "<?php\r\n";
                $content .= PhpBURN_Views::loadViewFile(self::$thisPath . DS . 'modelTemplate.html',$viewData, true);
                $content .= "\r\n?>\r\n";

                if(!is_dir(SYS_MODEL_PATH . $viewData['package'])) {
                    SYS_MODEL_PATH . $viewData['package'];
                    mkdir(SYS_MODEL_PATH . $viewData['package'],0777,true);

                }

                $file = $viewData['className'];
                $fileName = sprintf("%s%s",$file,SYS_MODEL_EXT);
                $filePath = SYS_MODEL_PATH . $viewData['package'];
                $file = fopen( $filePath . DS . $fileName ,'w+');
                fwrite($file, $content);
                fclose($file);

                $outputMessage = sprintf("[!Creating Model!]: %s",$filePath . DS . $fileName);
                PhpBURN_Message::output($outputMessage);

                unset($content);
            }

        } else {
            $outputMessage = sprintf("%s [!is not writable!]",SYS_MODEL_PATH);
            PhpBURN_Message::output($outputMessage);
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