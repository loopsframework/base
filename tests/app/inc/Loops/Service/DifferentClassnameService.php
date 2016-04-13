<?php

namespace Loops\Service;

use Loops;
use Loops\Service;

class DifferentClassnameService extends Service {
    protected static $classname = "DummyClass";
    
    public static function _getClassname(Loops $loops = NULL) {
        return static::getClassname($loops);
    }
}