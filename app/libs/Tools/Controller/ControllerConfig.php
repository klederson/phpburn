<?php
class PhpBURN_ControllerConfig {
    
    protected static $onCallActionBefore  = array();
    protected static $onCallActionAfter   = array();

    public function  __construct(array $config) {
        
    }

    public function addOnCallActionBefore($name, $func) {
        self::$onCallActionBefore[$name] = $func;
    }

    public function addOnCallActionAfter($name, $func) {
        self::$onCallActionAfter[$name] = $func;
    }

    public function getOnCallActionAfter($name = null) {
        return $name == null ? self::$onCallActionAfter : self::$onCallActionAfter[$name];
    }

    public function getOnCallActionBefore($name = null) {
        return $name == null ? self::$onCallActionBefore : self::$onCallActionBefore[$name];
    }
}

?>
