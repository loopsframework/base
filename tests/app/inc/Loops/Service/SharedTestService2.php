<?php

namespace Loops\Service;

use Loops;
use Loops\Service;

class SharedTestService2 extends Service {
    public static function isShared(Loops $loops) {
        return TRUE;
    }
}
