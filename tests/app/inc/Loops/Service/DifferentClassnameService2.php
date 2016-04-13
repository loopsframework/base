<?php

namespace Loops\Service;

use Loops;
use Loops\Service;

class DifferentClassnameService2 extends Service {
    protected static function getClassname(Loops $loops = NULL) {
        return "DummyClass";
    }
    
    public static function _getClassname(Loops $loops = NULL) {
        return static::getClassname($loops);
    }
}