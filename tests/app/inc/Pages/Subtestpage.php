<?php

namespace Pages;

use Loops\Annotations\Object;
use Loops\Annotations\Access\ReadOnly;

class Subtestpage extends Testpage {
    /**
     * @Object("DummyElement")
     * @ReadOnly
     */
    protected $test2;
}