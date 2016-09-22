<?php

require_once(__DIR__."/LoopsTestCase.php");

use Loops\Application\WebApplication;
use Loops\ErrorPage;

class LoopsErrorPageTest extends LoopsTestCase {
    function testCreation() {
        $app = new WebApplication(__DIR__."/app", "/");
        ob_start();
        $app->run();
        ob_end_clean();

        $errorpage = new ErrorPage;
        $this->assertEquals($errorpage->status_code, 404);

        $errorpage = new ErrorPage(501);
        $this->assertEquals($errorpage->status_code, 501);
    }

    function testPath() {
        $app = new WebApplication(__DIR__."/app", "/");
        ob_start();
        $app->run();
        ob_end_clean();
        $errorpage = new ErrorPage(404, $app->getLoops());
        $this->assertEquals("", $errorpage->getPagePath());

        $app = new WebApplication(__DIR__."/app", "/test");
        ob_start();
        $app->run();
        ob_end_clean();
        $errorpage = new ErrorPage(404, $app->getLoops());
        $this->assertEquals("test", $errorpage->getPagePath());

        $app = new WebApplication(__DIR__."/app", "/test/longerurl");
        ob_start();
        $app->run();
        ob_end_clean();
        $errorpage = new ErrorPage(404, $app->getLoops());
        $this->assertEquals("test/longerurl", $errorpage->getPagePath());
    }
}
