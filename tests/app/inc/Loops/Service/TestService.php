<?php

namespace Loops\Service;

use Loops;
use Loops\Service;

class TestService extends Service {
    public static function _getClassname(Loops $loops = NULL) {
        return static::getClassname($loops);
    }
}
