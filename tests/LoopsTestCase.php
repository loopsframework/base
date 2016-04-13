<?php

abstract class LoopsTestCase extends PHPUnit_Framework_TestCase {
    public function setUp() {
        spl_autoload_register(function($classname) {
            $classname = str_replace("\\", DIRECTORY_SEPARATOR, $classname);
            $filename = __DIR__."/app/inc/$classname.php";
            if(file_exists($filename)) {
                require_once($filename);
            }
        });
    }
}