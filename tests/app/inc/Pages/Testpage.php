<?php

namespace Pages;

use Loops\Page;
use Loops\Annotations\Object;
use Loops\Annotations\Access\ReadOnly;

class Testpage extends Page {
    /**
     * @Object("DummyElement")
     * @ReadOnly
     */
    protected $test;
}