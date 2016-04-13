<?php

namespace Loops\Application\LoopsAdmin;

use Loops\Exception;
use Loops\Object;
use Loops\Annotations\Admin\Help;
use Loops\Annotations\Admin\Action;

/**
 * @Help("help string")
 */
class TestModule extends Object {
    /**
     * @Action("help simpleAction")
     */
    public function simpleAction() {
        $this->application->stdout("action1");
    }
    
    /**
     * @Action("help failingAction")
     */
    public function failingAction() {
        return 123;
    }
    
    /**
     * @Action("help exceptionAction")
     */
    public function exceptingAction() {
        throw new Exception("Test Exception");
    }
    
    public function init_flagActionFlags($flags) {
        $flags->string("test", "default");
    }
    
    /**
     * @Action("help flagAction",init_flags="init_flagActionFlags")
     */
    public function flagAction($test) {
        $this->application->stdout($test);
    }
    
    public function init_dashedFlagActionFlags($flags) {
        $flags->string("test-flag", "default");
    }
    
    /**
     * @Action("help dashedFlagAction",init_flags="init_dashedFlagActionFlags")
     */
    public function dashedFlagAction($test_flag) {
        $this->application->stdout($test_flag);
    }
    
    /**
     * @Action("help otherAction")
     */
    public function otherAction($__arguments, $__module, $__action) {
        $this->application->stdout(implode(",", $__arguments)."\n");
        $this->application->stdout("$__module\n");
        $this->application->stdout("$__action\n");
    }
}