<?php

require_once(__DIR__."/LoopsTestCase.php");

use Loops\ArrayObject;
use Loops\Service;

class LoopsTestServiceMock {
    public $a;

    public function __construct($a = "a") {
        $this->a = $a;
    }
}

class LoopsTest extends LoopsTestCase {
    public function testDebugVar() {
        $loops = new Loops(new ArrayObject, TRUE);
        $this->assertTrue($loops->debug);

        $loops = new Loops(new ArrayObject, FALSE);
        $this->assertFalse($loops->debug);
    }

    public function testConfigService() {
        $config = new ArrayObject;
        $loops = new Loops($config);

        $this->assertSame($config, $loops->getService('config', $config));
    }

    public function testHasServices() {
        $loops = new Loops(new ArrayObject);

        $this->assertFalse($loops->hasService("test_service", FALSE));
        $this->assertTrue($loops->hasService("test_service"));

        $name = uniqid();
        $this->assertFalse($loops->hasService($name));
    }

    public function testCurrentLoops() {
        $loops = new Loops(new ArrayObject);
        $this->assertEquals($loops, Loops::getCurrentLoops());

        //new object, new current loopscontext
        $loops = new Loops(new ArrayObject);
        $this->assertEquals($loops, Loops::getCurrentLoops());
    }

    public function testRegisterService() {
        $loops = new Loops(new ArrayObject);

        $name = uniqid();
        $service = new stdClass;
        $loops->registerService($name, $service);
        $this->assertSame($service, $loops->getService($name));

        $name = uniqid();
        $callback = function() use ($service) { return $service; };
        $loops->registerService($name, $callback);
        $this->assertSame($service, $loops->getService($name));

        $name = uniqid();
        $string = "stdClass";
        $loops->registerService($name, $string);
        $this->assertInstanceOf("stdClass", $loops->getService($name));

        $name = uniqid();
        $string = "stdClass";
        $loops->registerService($name, $string, [], FALSE);
        $this->assertNotSame($loops->getService($name), $loops->getService($name));

        $name = uniqid();
        $string = "DummyClass";
        $loops->registerService($name, $string, [1,2]);
        $this->assertEquals(1, $loops->getService($name)->param1);
        $this->assertEquals(2, $loops->getService($name)->param2);

        $name = uniqid();
        $service = new stdClass;
        $loops->registerService($name, function($service) { return $service; }, [$service]);
        $this->assertSame($service, $loops->getService($name));
    }

    public function testCreateService() {
        $loops = new Loops(new ArrayObject);

        $name = uniqid();
        $service = "stdClass";
        $loops->registerService($name, $service);
        $this->assertSame($loops->getService($name), $loops->getService($name));
        $this->assertNotSame($loops->getService($name), $loops->createService($name));
    }

    public function testCreateServiceMergingConfig() {
        $name = uniqid();

        $loops = new Loops(ArrayObject::fromArray([$name => [ "a" => "b" ]]));
        $loops->registerService($name, "LoopsTestServiceMock");

        $service = $loops->createService($name, NULL, FALSE);
        $this->assertInstanceOf("LoopsTestServiceMock", $service);
        $this->assertSame("a", $service->a);

        $service = $loops->createService($name, NULL, TRUE);
        $this->assertInstanceOf("LoopsTestServiceMock", $service);
        $this->assertSame("b", $service->a);

        $service = $loops->createService($name, ArrayObject::fromArray(["a"=>"c"]), TRUE);
        $this->assertInstanceOf("LoopsTestServiceMock", $service);
        $this->assertSame("c", $service->a);
    }
}
