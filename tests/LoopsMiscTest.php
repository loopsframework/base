<?php

require_once(__DIR__."/LoopsTestCase.php");

use ReflectionClass;
use Pages\Testpage;
use Loops\Misc;
use Loops\Application\WebApplication;

class LoopsMiscTest_Mock {
    public $a = 1;
    protected $b = 2;
    private $c = 3;
    
    public $o1;
    public $o2;
    public $o3;
}

class LoopsMiscTest_Mock2 extends LoopsMiscTest_Mock {
    public function __clone() {
        //manual clone but ignore for unit tests here
    }
}

class LoopsMiscTest_Mock3 {
    public $counter = 0;
    
    public function doRecursion() {
        if($this->counter++ > 10) {
            return FALSE;
        }
        
        if(Misc::detectRecursion()) {
            return TRUE;
        }
        
        return $this->doRecursion();
    }
    
    public function doRecursionArg($arg = 0) {
        if($this->counter++ > 10) {
            return FALSE;
        }
        
        if(Misc::detectRecursion()) {
            return TRUE;
        }
        
        return $this->doRecursionArg($this->counter);
    }
    
    public function doRecursionArgTrue($arg = 0) {
        if($this->counter++ > 10) {
            return FALSE;
        }
        
        if(Misc::detectRecursion(TRUE)) {
            return TRUE;
        }
        
        return $this->doRecursionArgTrue($this->counter);
    }
}

class LoopsMiscTest_Mock4 {
    public $arg1;
    public $arg2;
    public $arg3;
    
    public $param1;
    public $param2;
    public $param3;
    
    public function __construct($arg1, $arg2 = "default", $arg3 = "default") {
        $this->arg1 = $arg1;
        $this->arg2 = $arg2;
        $this->arg3 = $arg3;
    }
}

class LoopsMiscTest_Mock5 {
    public function __construct() {
    }
}

class LoopsMiscTest_Mock6 {
    public $arg1;
    public $arg2;
    public $arg3;
    
    public $param1;
    public $param2;
    public $param3;
    
    public function __construct($arg1, $arg2, $arg3) {
        $this->arg1 = $arg1;
        $this->arg2 = $arg2;
        $this->arg3 = $arg3;
    }
}

class LoopsMiscTest extends LoopsTestCase {
    public function testGetRelativePath_FullToFull() {
        $s = DIRECTORY_SEPARATOR;
        
        //full paths
        $this->assertEquals(".", Misc::getRelativePath($s, $s));
        $this->assertEquals("test2".$s."test3",                     Misc::getRelativePath($s."test", $s."test".$s."test2".$s."test3"));
        $this->assertEquals("test2",                                Misc::getRelativePath($s."test", $s."test".$s."test2"));
        $this->assertEquals(".",                                    Misc::getRelativePath($s."test", $s."test"));
        $this->assertEquals("..",                                   Misc::getRelativePath($s."test", $s));
        $this->assertEquals("..".$s."test2",                        Misc::getRelativePath($s."test", $s."test2"));
        $this->assertEquals("..".$s."test2".$s."test3",             Misc::getRelativePath($s."test", $s."test2".$s."test3"));
        $this->assertEquals("..".$s."test2".$s."test3".$s."test4",  Misc::getRelativePath($s."test", $s."test2".$s."test3".$s."test4"));
        
        
    }
    
    public function testGetRelativePath_RelativeToRelative() {
        $s = DIRECTORY_SEPARATOR;
        
        $this->assertEquals("test2".$s."test3",                     Misc::getRelativePath("test", "test".$s."test2".$s."test3"));
        $this->assertEquals("test2",                                Misc::getRelativePath("test", "test".$s."test2"));
        $this->assertEquals(".",                                    Misc::getRelativePath("test", "test"));
        $this->assertEquals("..",                                   Misc::getRelativePath("test", "."));
        $this->assertEquals("..".$s."test2",                        Misc::getRelativePath("test", "test2"));
        $this->assertEquals("..".$s."test2".$s."test3",             Misc::getRelativePath("test", "test2".$s."test3"));
        $this->assertEquals("..".$s."test2".$s."test3".$s."test4",  Misc::getRelativePath("test", "test2".$s."test3".$s."test4"));
    }
    
    public function testGetRelativePath_RelativeToFull() {
        $s = DIRECTORY_SEPARATOR;
        $dir = getcwd();
        if($pos = strpos($dir, ":")) $dir = substr($dir, $pos+1);
        
        if($dir == $s) {
            $this->assertEquals(".",                    Misc::getRelativePath(".", $s));
            $this->assertEquals("..",            Misc::getRelativePath("test", $s));
            $this->assertEquals("..".$s."test2", Misc::getRelativePath("test", $s."test2"));
        }
        else {
            $parts = explode($s, trim($dir, $s));
            $count = count($parts);
            foreach(range(1, $count) as $i) {
                $prefix[$i] = implode($s, array_fill(0, $i, ".."));
            }
        
            $this->assertEquals($prefix[$count],                    Misc::getRelativePath(".", $s));
            $this->assertEquals($prefix[$count].$s."..",            Misc::getRelativePath("test", $s));
            $this->assertEquals($prefix[$count].$s."..".$s."test2", Misc::getRelativePath("test", $s."test2"));
            
            $this->assertEquals($prefix[$count-1],                  Misc::getRelativePath(".", $s.$parts[0]));
            $this->assertEquals($prefix[$count-1].$s."test",        Misc::getRelativePath(".", $s.$parts[0].$s."test"));
            $this->assertEquals($prefix[$count],                    Misc::getRelativePath("test", $s.$parts[0]));
        }
        
        $this->assertEquals(".",                                Misc::getRelativePath(".", $dir));
        $this->assertEquals("test",                             Misc::getRelativePath(".", $dir.$s."test"));
    }
    
    public function testGetRelativePath_FullToRelative() {
        $s = DIRECTORY_SEPARATOR;
        $dir = getcwd();
        if($pos = strpos($dir, ":")) $dir = substr($dir, $pos+1);
        
        if($dir == $s) {
            $this->assertEquals(".",             Misc::getRelativePath($s, "."));
            $this->assertEquals("..",            Misc::getRelativePath($s."test", "."));
            $this->assertEquals("test",          Misc::getRelativePath($s, "test"));
            $this->assertEquals("..".$s."test",  Misc::getRelativePath($s."test", "test"));
        }
        else {
            $this->assertEquals(substr($dir, 1),                    Misc::getRelativePath($s, "."));
            $this->assertEquals("..".$s.substr($dir, 1),            Misc::getRelativePath($s."test", "."));
            $this->assertEquals(substr($dir, 1).$s."test",          Misc::getRelativePath($s, "test"));
            $this->assertEquals("..".$s.substr($dir, 1).$s."test",  Misc::getRelativePath($s."test", "test"));
        }
    }
    
    public function testGetRelativePath_CurrentToFull() {
        $s = DIRECTORY_SEPARATOR;
        $dir = getcwd();
        if($pos = strpos($dir, ":")) $dir = substr($dir, $pos+1);
        if($dir == $s) {
            $this->assertEquals(".",                Misc::getRelativePath($s));
        }
        else {
            $parts = explode($s, trim($dir, $s));
            $count = count($parts);
            foreach(range(1, $count) as $i) {
                $prefix[$i] = implode($s, array_fill(0, $i, ".."));
            }
            
            $this->assertEquals($prefix[$count],                Misc::getRelativePath($s));
            $this->assertEquals($prefix[$count-1],              Misc::getRelativePath($s.$parts[0]));
            $this->assertEquals($prefix[$count-1].$s."test",    Misc::getRelativePath($s.$parts[0].$s."test"));
        }
        
        $this->assertEquals(".",                            Misc::getRelativePath($dir));
        $this->assertEquals("test",                         Misc::getRelativePath($dir.$s."test"));
    }
    
    public function testCamelize() {
        $this->assertEquals("Ucfirst", Misc::camelize("ucfirst"));
        $this->assertEquals("CamelCase", Misc::camelize("camel_case"));
        $this->assertEquals("ALongerExample", Misc::camelize("a_longer_example"));
        $this->assertEquals("Camel_Case", Misc::camelize("camel__case"));
        $this->assertEquals("CamelCase_", Misc::camelize("camel_case_"));
    }
    
    public function testUnderscore() {
        $this->assertEquals("ucfirst", Misc::underscore("Ucfirst"));
        $this->assertEquals("camel_case", Misc::underscore("CamelCase"));
        $this->assertEquals("a_longer_example", Misc::underscore("ALongerExample"));
        $this->assertEquals("camel__case", Misc::underscore("Camel_Case"));
        $this->assertEquals("camel_case_", Misc::underscore("CamelCase_"));
    }
    
    /**
     * @todo implement this
     */
    public function testServeFile() {
        
    }
    
    /**
     * @todo implement this
     */
    public function testReadPartialFile() {
    }
    
    public function testFlattenArray() {
        $test['a']           = 1;
        $test['b']['c']      = 2;
        $test['b']['d']      = 3;
        $test['e']['f']['g'] = 4;
        
        $this->assertEquals(['a' => 1, 'b.c' => 2, 'b.d' => 3, 'e.f.g' => 4], Misc::flattenArray($test));
        $this->assertEquals(['a' => 1, 'b-c' => 2, 'b-d' => 3, 'e-f-g' => 4], Misc::flattenArray($test, '-'));
        $this->assertEquals(['test_a' => 1, 'test_b.c' => 2, 'test_b.d' => 3, 'test_e.f.g' => 4], Misc::flattenArray($test, '.', 'test_'));
    }
    
    public function testUnflattenArray() {
        $test['a']           = 1;
        $test['b']['c']      = 2;
        $test['b']['d']      = 3;
        $test['e']['f']['g'] = 4;
        
        $flat['a']     = 1;
        $flat['b.c']   = 2;
        $flat['b.d']   = 3;
        $flat['e.f.g'] = 4;
        
        $this->assertEquals($test, Misc::unflattenArray($flat));
        
        $flat2['a']     = 1;
        $flat2['b-c']   = 2;
        $flat2['b-d']   = 3;
        $flat2['e-f-g'] = 4;
        
        $this->assertEquals($test, Misc::unflattenArray($flat2, "-"));
    }
    
    public function testRedirect() {
        $app2 = new WebApplication(__DIR__.DIRECTORY_SEPARATOR."app", "/");
        
        $app = new WebApplication(__DIR__.DIRECTORY_SEPARATOR."app", "/");

        $res = Misc::redirect("http://www.example.com");
        
        $this->assertEquals(302, $res);
        $this->assertContains("Location: http://www.example.com", $app->response->extra_header);
        
        $res = Misc::redirect("http://www.example.com", 301);
        $this->assertEquals(301, $res);
        
        $element = new Testpage;
        
        Misc::redirect($element->test);

        $this->assertContains("Location: /testpage/test", $app->response->extra_header);
        
        Misc::redirect("http://www.example.com/test", 302, $app2->loops);
        $this->assertNotContains("Location: http://www.example.com/test", $app->response->extra_header);
        $this->assertContains("Location: http://www.example.com/test", $app2->response->extra_header);
    }
    
    public function testDeepClone() {
        $a = new LoopsMiscTest_Mock;
        $b = new LoopsMiscTest_Mock;
        $c = new LoopsMiscTest_Mock;
        $d = new LoopsMiscTest_Mock;
        
        $a->o1 = $b;
        $a->o2 = $c;
        $a->o3 = $d;
        
        $b->o1 = $a;
        $b->o2 = $c;
        $b->o3 = $d;
        
        $clone = Misc::deepClone($a);
        
        $this->assertNotSame($clone, $a);
        $this->assertNotSame($clone->o1, $a->o1);
        $this->assertNotSame($clone->o2, $a->o2);
        $this->assertNotSame($clone->o3, $a->o3);
        $this->assertNotSame($clone->o1->o1, $a->o1->o1);
        $this->assertNotSame($clone->o1->o2, $a->o1->o2);
        $this->assertNotSame($clone->o1->o3, $a->o1->o3);
        
        $this->assertSame($clone->o2, $clone->o1->o2);
        $this->assertSame($clone->o3, $clone->o1->o3);
        $this->assertSame($clone->o1->o1, $clone);
    }
    
    public function testDeepCloneWithMagicMethod() {
        $a = new LoopsMiscTest_Mock;
        $b = new LoopsMiscTest_Mock2;
        $c = new LoopsMiscTest_Mock;
        
        $a->o1 = $b;
        $a->o2 = $c;
        
        $b->o1 = $a;
        $b->o2 = $c;
        
        $clone = Misc::deepClone($a);
        
        $this->assertSame($a, $clone->o1->o1);
        $this->assertSame($c, $clone->o1->o2);
    }
    
    public function testGetLocale() {
        $this->assertEquals(setlocale(LC_ALL, 0), Misc::getLocale());
        $this->assertEquals(setlocale(LC_NUMERIC, 0), Misc::getLocale(LC_NUMERIC));
    }
    
    public function testRecursiveUnlink() {
        $app = new WebApplication(__DIR__."/app", "/");
        mkdir($app->cache_dir."/test");
        mkdir($app->cache_dir."/test/test");
        file_put_contents($app->cache_dir."/test/test1", "test");
        
        $this->assertTrue(Misc::recursiveUnlink($app->cache_dir."/test"));
    }
    
    public function testRecursiveMkdir() {
        $app = new WebApplication(__DIR__."/app", "/");
        $this->assertTrue(Misc::recursiveMkdir($app->cache_dir."/test/test"));
        $this->assertTrue(Misc::recursiveUnlink($app->cache_dir."/test"));
    }
    
    public function testDetectRecursion() {
        $a = new LoopsMiscTest_Mock3;
        $b = new LoopsMiscTest_Mock3;
        $c = new LoopsMiscTest_Mock3;
        
        $this->assertTrue($a->doRecursion());
        $this->assertTrue($b->doRecursionArg());
        $this->assertFalse($b->doRecursionArgTrue());
    }
    
    public function testReflectionInstance_stdobject() {
        $object = Misc::reflectionInstance("stdClass", []);
        $this->assertInstanceOf("stdClass", $object);
    }
    
    public function testReflectionInstance_noargs() {
        $object = Misc::reflectionInstance("LoopsMiscTest_Mock5", []);
        $this->assertInstanceOf("LoopsMiscTest_Mock5", $object);
    }
    
    public function testReflectionInstance_noargs_params() {
        $args = Misc::getReflectionArgs((new ReflectionClass("LoopsMiscTest_Mock5"))->getConstructor());
        $this->assertSame([], $args);
    }
    
    public function testReflectionInstance_missingparams1() {
        $this->setExpectedException("Loops\Exception");
        $object = Misc::reflectionInstance("LoopsMiscTest_Mock4", []);
    }
    
    public function testReflectionInstance_missingparams1_params() {
        $args = Misc::getReflectionArgs((new ReflectionClass("LoopsMiscTest_Mock4"))->getConstructor(), [], $missing);
        $this->assertFalse($args);
        $this->assertSame([0 => "arg1"], $missing);
    }
    
    public function testReflectionInstance_missingparams2() {
        $this->setExpectedException("Loops\Exception");
        $object = Misc::reflectionInstance("LoopsMiscTest_Mock4", [ "arg2" => "test" ]);
    }
    
    public function testReflectionInstance_missingparams2_params() {
        $args = Misc::getReflectionArgs((new ReflectionClass("LoopsMiscTest_Mock4"))->getConstructor(), [ "arg2" => "test" ], $missing);
        $this->assertFalse($args);
        $this->assertSame([0 => "arg1"], $missing);
    }
    
    public function testReflectionInstance_missingparams3() {
        $this->setExpectedException("Loops\Exception");
        $object = Misc::reflectionInstance("LoopsMiscTest_Mock6", []);
    }
    
    public function testReflectionInstance_missingparams3_params() {
        $args = Misc::getReflectionArgs((new ReflectionClass("LoopsMiscTest_Mock6"))->getConstructor(), [], $missing);
        $this->assertFalse($args);
        $this->assertSame([0 => "arg1", 1 => "arg2", 2 => "arg3"], $missing);
    }
    
    public function testReflectionInstance_missingparams4() {
        $this->setExpectedException("Loops\Exception");
        $object = Misc::reflectionInstance("LoopsMiscTest_Mock6", [ "arg2" => "test" ]);
    }
    
    public function testReflectionInstance_missingparams4_params() {
        $args = Misc::getReflectionArgs((new ReflectionClass("LoopsMiscTest_Mock6"))->getConstructor(), [ "test" ], $missing);
        $this->assertFalse($args);
        $this->assertSame([1 => "arg2", 2 => "arg3"], $missing);
        
        $args = Misc::getReflectionArgs((new ReflectionClass("LoopsMiscTest_Mock6"))->getConstructor(), [ "arg2" => "test" ], $missing);
        $this->assertFalse($args);
        $this->assertSame([0 => "arg1", 2 => "arg3"], $missing);
    }
    
    public function testReflectionInstance_reqparamsonly() {
        $object = Misc::reflectionInstance("LoopsMiscTest_Mock4", [ "arg1" ]);
        $this->assertEquals("arg1", $object->arg1);
        $this->assertEquals("default", $object->arg2);
        $this->assertEquals("default", $object->arg3);
        
        $object = Misc::reflectionInstance("LoopsMiscTest_Mock4", [ "arg1" => "test" ]);
        $this->assertEquals("test", $object->arg1);
        $this->assertEquals("default", $object->arg2);
        $this->assertEquals("default", $object->arg3);
    }
    
    public function testReflectionInstance_reqparamsonly_params() {
        $args = Misc::getReflectionArgs((new ReflectionClass("LoopsMiscTest_Mock4"))->getConstructor(), [ "arg1" ]);
        $this->assertSame(["arg1", "default", "default"], $args);
        
        $args = Misc::getReflectionArgs((new ReflectionClass("LoopsMiscTest_Mock4"))->getConstructor(), [ "arg1" => "test" ]);
        $this->assertSame(["test", "default", "default"], $args);
    }
    
    public function testReflectionInstance_somedefaults() {
        $object = Misc::reflectionInstance("LoopsMiscTest_Mock4", [ "arg1" => "test1", "arg3" => "test3" ]);
        $this->assertEquals("test1", $object->arg1);
        $this->assertEquals("default", $object->arg2);
        $this->assertEquals("test3", $object->arg3);
    }
    
    public function testReflectionInstance_somedefaults_params() {
        $args = Misc::getReflectionArgs((new ReflectionClass("LoopsMiscTest_Mock4"))->getConstructor(), [ "arg1" => "test1", "arg3" => "test3" ]);
        $this->assertSame(["test1", "default", "test3"], $args);
    }
    
    public function testReflectionInstance_additionalparams() {
        $object = Misc::reflectionInstance("LoopsMiscTest_Mock4", [ "arg1" => "test1", "param1" => "test" ]);
        $this->assertNull($object->param1);
        $this->assertNull($object->param2);
        $this->assertNull($object->param3);
        
        $object = Misc::reflectionInstance("LoopsMiscTest_Mock4", [ "arg1" => "test1", "param1" => "test", "param2" => "test", "param3" => "test" ], TRUE);
        $this->assertEquals("test", $object->param1);
        $this->assertEquals("test", $object->param2);
        $this->assertEquals("test", $object->param3);
    }
    
    public function testReflectionInstance_setinclude() {
        $object = Misc::reflectionInstance("LoopsMiscTest_Mock4", [ "arg1" => "test1", "param1" => "test", "param2" => "test", "param3" => "test" ], TRUE, [ "param2" ]);
        $this->assertNull($object->param1);
        $this->assertEquals("test", $object->param2);
        $this->assertNull($object->param3);
    }
    
    public function testReflectionInstance_setexclude() {
        $object = Misc::reflectionInstance("LoopsMiscTest_Mock4", [ "arg1" => "test1", "param1" => "test", "param2" => "test", "param3" => "test" ], TRUE, FALSE, [ "param2" ]);
        $this->assertEquals("test", $object->param1);
        $this->assertNull($object->param2);
        $this->assertEquals("test", $object->param3);
    }
    
    public function testFullPathsRelative() {
        $cwd = getcwd();
        $this->assertEquals(rtrim($cwd, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'test', Misc::fullPath('test'));
    }
    
    public function testFullPathsFull() {
        $this->assertEquals('/test', Misc::fullPath('/test'));
        $this->assertEquals('C:\\test', Misc::fullPath('C:\\test'));
    }
    
    public function testFullPathsRelativeOtherCws() {
        $this->assertEquals('/test'.DIRECTORY_SEPARATOR.'test', Misc::fullPath('test', '/test'));
        $this->assertEquals('/test'.DIRECTORY_SEPARATOR.'test', Misc::fullPath('test', '/test'.DIRECTORY_SEPARATOR));
        $this->assertEquals('C:\\test'.DIRECTORY_SEPARATOR.'test', Misc::fullPath('test', 'C:\\test'));
        $this->assertEquals('C:\\test'.DIRECTORY_SEPARATOR.'test', Misc::fullPath('test', 'C:\\test'.DIRECTORY_SEPARATOR));
        $this->assertEquals('/test', Misc::fullPath('/test', '/test'));
        $this->assertEquals('C:\\test', Misc::fullPath('C:\\test', '/test'));
    }
    
    public function testFullPathsUpperExeption() {
        $this->setExpectedException("Loops\Exception");
        Misc::fullPath('../test');
    }
    
    public function testBadPathsExeption() {
        $this->setExpectedException("Loops\Exception");
        Misc::fullPath('../test', '/', TRUE);
        Misc::fullPath('../test', 'C:\\', TRUE);
    }
    
    public function testFullPathsUpper() {
        $cwd = getcwd();
        $parts = explode(DIRECTORY_SEPARATOR, $cwd);
        array_pop($parts);
        $path = implode(DIRECTORY_SEPARATOR, $parts);
        if($cwd != "/") {
            $this->assertEquals($path.DIRECTORY_SEPARATOR.'test', Misc::fullPath('../test', NULL, TRUE));
        }
        $this->assertEquals('/test'.DIRECTORY_SEPARATOR.'test', Misc::fullPath('../test', '/test'.DIRECTORY_SEPARATOR.'sub', TRUE));
        $this->assertEquals('/test'.DIRECTORY_SEPARATOR.'test', Misc::fullPath('../test', '/test'.DIRECTORY_SEPARATOR.'sub'.DIRECTORY_SEPARATOR, TRUE));
        $this->assertEquals('C:\\test'.DIRECTORY_SEPARATOR.'test', Misc::fullPath('../test', 'C:\\test'.DIRECTORY_SEPARATOR.'sub', TRUE));
        $this->assertEquals('C:\\test'.DIRECTORY_SEPARATOR.'test', Misc::fullPath('../test', 'C:\\test'.DIRECTORY_SEPARATOR.'sub'.DIRECTORY_SEPARATOR, TRUE));
    }
}
