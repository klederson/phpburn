<?php
class PhpBURN_Reverse {
    public function init() {
        $packages = PhpBURN_Configuration::getConfig();

        print "<pre>";
        foreach($packages as $package => $packageConfig) {
            print_r($packageConfig);
        }

        $class = new _ReverseClass('teste','syscore');

        $tables = $class->getConnection()->getTables();

        foreach($tables as $table) {
            $fieldData = $class->getConnection()->describe($table);

//            var_dump($fieldData);

            foreach($fieldData as $data) {
                $options['primary'] = $data[4];
                $options['not_null'] = $data[5];
                $options['default_value'] = $data[6];
                $options['auto_increment'] = $data[7];

                $fields[] = self::generateField($data[0], $data[2], $data[3], $options);
            }

            print "============== $table ============== <br/>";
            print_r($fields);
            
            $fks = !is_array($fks) ? array() : $fks;
            $localArr = $class->getConnection()->getForeignKeys($table);

            print_r($localArr);

            foreach($localArr as $index => $keyArr) {
                $fks[$index][] = $keyArr;
            }

//          post-reverse
            unset($fields);

            
        }
        print "============== <b>FINAL</b> ============== <br/>";
        var_dump($fks);

        
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
}

class _ReverseClass extends PhpBURN_Core {
    public $_tablename = null;
    public $_package = null;

    public function _mapping() {
        
    }
}
?>