<?php
namespace PhpBURN\Spices;

class Spices {
    public static function loadSpice($name = null) {
        $name = $name == null ? '*.' : sprintf('%s.', $name);
        $loadPath = sprintf("%s%s%s",SYS_SPICES_PATH, '*.', SYS_SPICES_EXT);

        foreach ( glob( $loadPath ) as $filename) {
            require_once($filename);
        }
        
        unset($loadPath);
    }
}
?>