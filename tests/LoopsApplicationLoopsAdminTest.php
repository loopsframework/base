<?php

require_once(__DIR__."/LoopsTestCase.php");

use Loops\Application\LoopsAdmin;

class LoopsApplicationLoopsAdminTest extends LoopsTestCase {
    private function content(SplFileObject $file) {
        $result = "";
        $file->fseek(0);
        while(!$file->eof()) $result .= $file->fread(1024);
        return $result;
    }
    
    public function testNoInput() {
        $stdout = new SplFileObject("php://memory", "w");
        $stderr = new SplFileObject("php://memory", "w");
        
        $app = new LoopsAdmin(__DIR__."/app", ["loops"], $stdout, $stderr);
        
        $this->assertNotEquals(0, $app->run());
    }
    
    public function testHelp() {
        $stdout = new SplFileObject("php://memory", "w");
        $stderr = new SplFileObject("php://memory", "w");
        
        $app = new LoopsAdmin(__DIR__."/app", ["loops", "help"], $stdout, $stderr);
        $this->assertEquals(0, $app->run());
    }
    
    public function testHelpModule() {
        $stdout = new SplFileObject("php://memory", "w");
        $stderr = new SplFileObject("php://memory", "w");
        
        $app = new LoopsAdmin(__DIR__."/app", ["loops", "help", "test_module"], $stdout, $stderr);
        $this->assertEquals(0, $app->run());
        
        $out = $this->content($stdout);
        
        $this->assertStringMatchesFormat("%Ahelp string%A", $out);
    }
    
    public function testHelpNoModule() {
        $stdout = new SplFileObject("php://memory", "w");
        $stderr = new SplFileObject("php://memory", "w");
        
        $app = new LoopsAdmin(__DIR__."/app", ["loops", "help", "non_existing_module"], $stdout, $stderr);
        $this->assertNotEquals(0, $app->run());
    }
    
    public function testHelpAction() {
        $stdout = new SplFileObject("php://memory", "w");
        $stderr = new SplFileObject("php://memory", "w");
        
        $app = new LoopsAdmin(__DIR__."/app", ["loops", "help", "test_module", "simple_action"], $stdout, $stderr);
        $this->assertEquals(0, $app->run());
        
        $out = $this->content($stdout);
        
        $this->assertStringMatchesFormat("%Ahelp simpleAction%A", $out);
    }
    
    public function testHelpNoAction() {
        $stdout = new SplFileObject("php://memory", "w");
        $stderr = new SplFileObject("php://memory", "w");
        
        $app = new LoopsAdmin(__DIR__."/app", ["loops", "help", "test_module", "non_existing_action"], $stdout, $stderr);
        $this->assertNotEquals(0, $app->run());
    }
    
    public function testNoModule() {
        $stdout = new SplFileObject("php://memory", "w");
        $stderr = new SplFileObject("php://memory", "w");
        
        $app = new LoopsAdmin(__DIR__."/app", ["loops", "non_existing_module"], $stdout, $stderr);
        $this->assertNotEquals(0, $app->run());
    }
    
    public function testNoAction() {
        $stdout = new SplFileObject("php://memory", "w");
        $stderr = new SplFileObject("php://memory", "w");
        
        $app = new LoopsAdmin(__DIR__."/app", ["loops", "test_module", "non_existing_action"], $stdout, $stderr);
        $this->assertNotEquals(0, $app->run());
    }
    
    public function testSimpleAction() {
        $stdout = new SplFileObject("php://memory", "w");
        $stderr = new SplFileObject("php://memory", "w");
        
        $app = new LoopsAdmin(__DIR__."/app", ["loops", "test_module", "simple_action"], $stdout, $stderr);
        
        $this->assertEquals(0, $app->run());
        
        $out = $this->content($stdout);
        
        $this->assertEquals("action1", $out);
    }
    
    public function testFailingAction() {
        $stdout = new SplFileObject("php://memory", "w");
        $stderr = new SplFileObject("php://memory", "w");
        
        $app = new LoopsAdmin(__DIR__."/app", ["loops", "test_module", "failing_action"], $stdout, $stderr);
        $this->assertEquals(123, $app->run());
    }
    
    public function testExceptionAction() {
        $stdout = new SplFileObject("php://memory", "w");
        $stderr = new SplFileObject("php://memory", "w");
        
        $app = new LoopsAdmin(__DIR__."/app", ["loops", "test_module", "excepting_action"], $stdout, $stderr);
        $this->assertNotEquals(0, $app->run());
    }
    
    public function testFlagActionDefault() {
        $stdout = new SplFileObject("php://memory", "w");
        $stderr = new SplFileObject("php://memory", "w");
        
        $app = new LoopsAdmin(__DIR__."/app", ["loops", "test_module", "flag_action"], $stdout, $stderr);
        $this->assertEquals(0, $app->run());
        
        $out = $this->content($stdout);
        
        $this->assertEquals("default", $out);
    }
    
    public function testFlagAction() {
        $stdout = new SplFileObject("php://memory", "w");
        $stderr = new SplFileObject("php://memory", "w");
        
        $app = new LoopsAdmin(__DIR__."/app", ["loops", "--test=meh", "test_module", "flag_action"], $stdout, $stderr);
        $this->assertEquals(0, $app->run());
        
        $out = $this->content($stdout);
        
        $this->assertEquals("meh", $out);
    }
    
    public function testDashedFlagAction() {
        $stdout = new SplFileObject("php://memory", "w");
        $stderr = new SplFileObject("php://memory", "w");
        
        $app = new LoopsAdmin(__DIR__."/app", ["loops", "--test-flag=meh", "test_module", "dashed_flag_action"], $stdout, $stderr);
        $this->assertEquals(0, $app->run());
        
        $out = $this->content($stdout);
        
        $this->assertEquals("meh", $out);
    }
    
    public function testOtherArguments() {
        $stdout = new SplFileObject("php://memory", "w");
        $stderr = new SplFileObject("php://memory", "w");
        
        $app = new LoopsAdmin(__DIR__."/app", ["loops", "test_module", "other_action"], $stdout, $stderr);
        $this->assertEquals(0, $app->run());
        
        $out = $this->content($stdout);
        
        $this->assertEquals("\ntest_module\nother_action\n", $out);
    }
    
    public function testOtherArgumentsExtra() {
        $stdout = new SplFileObject("php://memory", "w");
        $stderr = new SplFileObject("php://memory", "w");
        
        $app = new LoopsAdmin(__DIR__."/app", ["loops", "test_module", "other_action", "extra", "arguments"], $stdout, $stderr);
        $this->assertEquals(0, $app->run());
        
        $out = $this->content($stdout);
        
        $this->assertEquals("extra,arguments\ntest_module\nother_action\n", $out);
    }
}