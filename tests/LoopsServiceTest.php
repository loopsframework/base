<?php

require_once(__DIR__."/LoopsTestCase.php");

use Loops\ArrayObject;
use Loops\Application\WebApplication;

class LoopsServiceTest extends LoopsTestCase {
    public function testSharedService() {
        $app = new WebApplication(__DIR__."/app", "/");
        $loops = $app->getLoops();
        
        $service1 = $loops->getService("shared_test_service");
        $service2 = $loops->getService("shared_test_service");
        
        $this->assertInstanceOf("Loops\ServiceInterface", $service1);
        $this->assertInstanceOf("Loops\ServiceInterface", $service2);
        
        $this->assertSame($service1, $service2);
    }
    
    public function testSharedService2() {
        $app = new WebApplication(__DIR__."/app", "/");
        $loops = $app->getLoops();
        
        $service1 = $loops->getService("shared_test_service2");
        $service2 = $loops->getService("shared_test_service2");
        
        $this->assertInstanceOf("Loops\ServiceInterface", $service1);
        $this->assertInstanceOf("Loops\ServiceInterface", $service2);
        
        $this->assertSame($service1, $service2);
    }
    
    public function testNonSharedService() {
        $app = new WebApplication(__DIR__."/app", "/");
        $loops = $app->getLoops();
        
        $service1 = $loops->getService("non_shared_test_service");
        $service2 = $loops->getService("non_shared_test_service");
        
        $this->assertInstanceOf("Loops\ServiceInterface", $service1);
        $this->assertInstanceOf("Loops\ServiceInterface", $service2);
        
        $this->assertNotSame($service1, $service2);
    }
    
    public function testNonSharedService2() {
        $app = new WebApplication(__DIR__."/app", "/");
        $loops = $app->getLoops();
        
        $service1 = $loops->getService("non_shared_test_service2");
        $service2 = $loops->getService("non_shared_test_service2");
        
        $this->assertInstanceOf("Loops\ServiceInterface", $service1);
        $this->assertInstanceOf("Loops\ServiceInterface", $service2);
        
        $this->assertNotSame($service1, $service2);
    }
    
    public function testDefaultConfigService() {
        $app = new WebApplication(__DIR__."/app", "/");
        $loops = $app->getLoops();
        
        $config = Loops\Service\DefaultConfigTestService::_getDefaultConfig($loops);
        
        $this->assertInstanceOf("Loops\ArrayObject", $config);
        $this->assertSame("b", $config["a"]);
        
        $service = $loops->getService("default_config_test_service");
        
        $this->assertInstanceOf("Loops\Service\DefaultConfigTestService", $service);
        
        $this->assertSame("b", $service->a);
    }
    
    public function testDefaultConfigService2() {
        $app = new WebApplication(__DIR__."/app", "/");
        $loops = $app->getLoops();
        
        $config = Loops\Service\DefaultConfigTestService2::_getDefaultConfig($loops);
        
        $this->assertInstanceOf("Loops\ArrayObject", $config);
        $this->assertSame("b", $config["a"]);
        
        $service = $loops->getService("default_config_test_service2");
        
        $this->assertInstanceOf("Loops\Service\DefaultConfigTestService2", $service);
        
        $this->assertSame("b", $service->a);
    }
    
    public function testDefaultClassname() {
        $app = new WebApplication(__DIR__."/app", "/");
        $loops = $app->getLoops();
        
        $classname = Loops\Service\TestService::_getClassname($loops);
        
        $this->assertSame("Loops\Service\TestService", $classname);
    }
    
    public function testDifferentClassname() {
        $app = new WebApplication(__DIR__."/app", "/");
        $loops = $app->getLoops();
        
        $classname = Loops\Service\DifferentClassnameService::_getClassname($loops);
        
        $this->assertSame("DummyClass", $classname);
        $this->assertInstanceof("DummyClass", $loops->getService("different_classname_service"));
    }
    
    public function testDifferentClassname2() {
        $app = new WebApplication(__DIR__."/app", "/");
        $loops = $app->getLoops();
        
        $classname = Loops\Service\DifferentClassnameService2::_getClassname($loops);
        
        $this->assertSame("DummyClass", $classname);
        $this->assertInstanceof("DummyClass", $loops->getService("different_classname_service2"));
    }
    
    public function testConfigNoMerging() {
        $app = new WebApplication(__DIR__."/app", "/");
        $loops = $app->getLoops();
        
        $config = Loops\Service\DefaultConfigTestService::getConfig($loops, new ArrayObject);
        
        $this->assertInstanceof("Loops\ArrayObject", $config);
        $this->assertSame(3, $config->count());
        $this->assertSame("b", $config["a"]);
        $this->assertSame("test", $config["test"]);
        $this->assertSame($loops, $config["loops"]);
    }
    
    public function testConfigMerge() {
        $app = new WebApplication(__DIR__."/app", "/");
        $loops = $app->getLoops();
        
        $config = Loops\Service\DefaultConfigTestService::getConfig($loops, new ArrayObject(["other"=>"other"]));
        
        $this->assertInstanceof("Loops\ArrayObject", $config);
        $this->assertSame(4, $config->count());
        $this->assertSame("b", $config["a"]);
        $this->assertSame("test", $config["test"]);
        $this->assertSame("other", $config["other"]);
        $this->assertSame($loops, $config["loops"]);
    }
    
    public function testConfigMergeOverwrite() {
        $app = new WebApplication(__DIR__."/app", "/");
        $loops = $app->getLoops();
        
        $userconfig = new ArrayObject(["a"=>"c"]);
        
        $config = Loops\Service\DefaultConfigTestService::getConfig($loops, $userconfig);
        
        $this->assertInstanceof("Loops\ArrayObject", $config);
        $this->assertSame(3, $config->count());
        $this->assertSame("c", $config["a"]);
        $this->assertSame("test", $config["test"]);
        $this->assertSame($loops, $config["loops"]);
        
        $service = $loops->createService("default_config_test_service", $userconfig);
        
        $this->assertInstanceOf("Loops\Service\DefaultConfigTestService", $service);
        
        $this->assertSame("c", $service->a);
    }
    
    public function testConfigNoMergingLoopsConfig() {
        $app = new WebApplication(__DIR__."/app", "/");
        $loops = $app->getLoops();
        
        $config = $loops->getService("config");
        $this->assertInstanceOf("Loops\ArrayObject", $config);
        $this->assertEmpty(@$config["default_config_test_service"]);
        
        $config = Loops\Service\DefaultConfigTestService::getConfig($loops);
        
        $this->assertInstanceof("Loops\ArrayObject", $config);
        $this->assertSame(3, $config->count());
        $this->assertSame("b", $config["a"]);
        $this->assertSame("test", $config["test"]);
        $this->assertSame($loops, $config["loops"]);
    }
    
    public function testConfigMergeLoopsConfig() {
        $app = new WebApplication(__DIR__."/app", "/");
        $loops = $app->getLoops();
        
        $config = $loops->getService("config");
        $this->assertInstanceOf("Loops\ArrayObject", $config);
        $section = $config["config_test_service"];
        $this->assertInstanceOf("Loops\ArrayObject", $section);
        
        $config = Loops\Service\ConfigTestService::getConfig($loops);
        
        $this->assertInstanceof("Loops\ArrayObject", $config);
        $this->assertSame(4, $config->count());
        $this->assertSame("c", $config["a"]);
        $this->assertSame("test", $config["test"]);
        $this->assertSame("other", $config["other"]);
        $this->assertSame($loops, $config["loops"]);
        
        $service = $loops->getService("config_test_service");
        
        $this->assertInstanceOf("Loops\Service\ConfigTestService", $service);
        
        $this->assertSame("c", $service->a);
    }
    
    public function testGetService() {
        $app = new WebApplication(__DIR__."/app", "/");
        $loops = $app->getLoops();
        
        $service = Loops\Service\ConfigTestService::getService(new ArrayObject, $loops);
        $this->assertInstanceOf("Loops\Service\ConfigTestService", $service);
    }
    
    public function testGetServiceDifferentClassAndParams() {
        $app = new WebApplication(__DIR__."/app", "/");
        $loops = $app->getLoops();
        
        $service = Loops\Service\DifferentClassnameService::getService(new ArrayObject, $loops);
        $this->assertInstanceOf("DummyClass", $service);
        
        $service = Loops\Service\DifferentClassnameService::getService(new ArrayObject(["param1" => "test"]), $loops);
        $this->assertInstanceOf("DummyClass", $service);
        $this->assertSame("test", $service->param1);
    }
}
