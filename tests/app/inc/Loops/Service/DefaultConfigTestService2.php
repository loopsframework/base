<?php

namespace Loops\Service;

use Loops;
use Loops\ArrayObject;
use Loops\Service;

class DefaultConfigTestService2 extends Service {
    public $a;
    
    public function __construct($a = "a", Loops $loops = NULL) {
        $this->a = $a;
    }
    
    protected static function getDefaultConfig(Loops $loops = NULL) {
        $result = new ArrayObject;
        $result["test"] = "test";
        $result["a"] = "b";
        return $result;
    }
    
    public static function _getDefaultConfig(Loops $loops = NULL) {
        return static::getDefaultConfig($loops);
    }
}