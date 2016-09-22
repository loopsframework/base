<?php

namespace Loops\Service;

use Loops\Service;

class NonSharedTestService extends Service {
    protected static $shared = FALSE;
}
