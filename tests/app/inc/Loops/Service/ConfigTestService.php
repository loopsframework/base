<?php

namespace Loops\Service;

use Loops;
use Loops\ArrayObject;
use Loops\Service;

class ConfigTestService extends Service {
    protected static $default_config = [ "test" => "test", "a" => "b" ];

    public $a;

    public function __construct($a = "a", Loops $loops = NULL) {
        $this->a = $a;
    }
}
