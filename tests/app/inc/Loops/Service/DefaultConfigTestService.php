<?php

namespace Loops\Service;

use Loops;
use Loops\Service;

class DefaultConfigTestService extends Service {
    protected static $default_config = [ "test" => "test", "a" => "b" ];
    
    public $a;
    
    public function __construct($a = "a", Loops $loops = NULL) {
        $this->a = $a;
    }
    
    public static function _getDefaultConfig(Loops $loops = NULL) {
        return static::getDefaultConfig($loops);
    }
}