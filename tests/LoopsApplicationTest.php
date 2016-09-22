<?php

require_once(__DIR__."/LoopsTestCase.php");

use Loops\Application;
use Loops\ArrayObject;

class TestApplication extends Application {
    public function run() {
    }
}

class LoopsApplicationTest extends LoopsTestCase {
    function testDefaultsettings() {
        $app_dir = __DIR__."/app";
        $cache_dir = "/tmp";

        $app = new TestApplication($app_dir, $cache_dir);

        $this->assertEquals($app_dir, $app->app_dir);
        $this->assertEquals($cache_dir, $app->cache_dir);
        $this->assertEquals(["$app_dir/inc"], $app->include_dir);
        $this->assertEquals("$app_dir/boot.php", $app->boot);
        $this->assertEquals(TRUE, $app->loops->debug);
    }

    function testAppservice() {
        $app = new TestApplication(__DIR__."/app", "/tmp");
        $this->assertSame($app, $app->application);
    }

    function testCreatewithloops() {
        $uid = uniqid();
        $loops = new Loops(new ArrayObject(['test'=>$uid]));
        $app = new TestApplication(__DIR__."/app", "/tmp", $loops);
        $this->assertSame($loops, $app->loops);
        $this->assertEquals($uid, $app->config->test);
    }

    function testCreatewitharrayconfig() {
        $uid = uniqid();
        $config = new ArrayObject(['test'=>$uid]);
        $app = new TestApplication(__DIR__."/app", "/tmp", $config);
        $this->assertEquals($uid, $app->config->test);
    }

    function testStringconfig() {
        $app = new TestApplication(__DIR__."/app", "/tmp", "config.php");
        $this->assertEquals("test", $app->config->test);

        $app = new TestApplication(__DIR__."/app", "/tmp", "config2.php");
        $this->assertEquals("test2", $app->config->test);
    }

    function testDefinedClasses() {
        $app = new TestApplication(__DIR__."/app", "/tmp");
        $this->assertContains("Pages\Testpage", $app->definedClasses());
        $this->assertContains("Loops\Service\TestService", $app->definedClasses());
    }

    function testBooted() {
        $app = new TestApplication(__DIR__."/app", "/tmp");
        $app->run();
        $this->assertTrue($app->config->booted);
    }
}
