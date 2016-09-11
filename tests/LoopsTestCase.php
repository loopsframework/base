<?php

use Loops\Application\WebApplication;

abstract class LoopsTestCase extends PHPUnit_Framework_TestCase {
    /**
     * The loops application in tests/app
     */
    protected $app;

    public static function setUpBeforeClass() {
        spl_autoload_register(function($classname) {
            $classname = str_replace("\\", DIRECTORY_SEPARATOR, $classname);
            $filename = __DIR__."/app/inc/$classname.php";
            if(file_exists($filename)) {
                require_once($filename);
            }
        });
    }

    public function setUp() {
        $this->warning_enabled = PHPUnit_Framework_Error_Warning::$enabled;
        $this->notice_enabled = PHPUnit_Framework_Error_Notice::$enabled;
        $this->deprecated_enabled = PHPUnit_Framework_Error_Deprecated::$enabled;

        $this->app = new WebApplication(__DIR__."/app", "/");
    }

    public function tearDown() {
        unset($this->app);

        PHPUnit_Framework_Error_Warning::$enabled = $this->warning_enabled;
        PHPUnit_Framework_Error_Notice::$enabled = $this->notice_enabled;
        PHPUnit_Framework_Error_Deprecated::$enabled = $this->deprecated_enabled;
    }
}
