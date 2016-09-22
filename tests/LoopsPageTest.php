<?php

require_once(__DIR__."/LoopsTestCase.php");

use Loops\ArrayObject;
use Loops\Page;
use Loops\Application\WebApplication;
use Pages\Testpage;
use Pages\Subtestpage;
use Pages\_\Parampage;
use Pages\_\Deeper\_;
use Pages\_\Deeper\Index;

class InvalidPage extends Page {

}

class LoopsPageTest extends LoopsTestCase {
    public function testConstructDefaults() {
        $app = new WebApplication(__DIR__."/app", "/testpage");
        $loops = $app->getLoops();

        $page = new Testpage;

        $this->assertSame(Loops::getCurrentLoops(), $page->getLoops());
        $this->assertSame([], $page->parameter);
    }

    public function testConstructPageParameter() {
        $app = new WebApplication(__DIR__."/app", "/testpage");
        $loops = $app->getLoops();

        $this->assertNotSame($other, $loops);

        $parameter = ["param","param2"];

        $page = new Testpage($parameter);

        $this->assertSame($parameter, $page->parameter);
    }

    public function testConstructLoopsParameter() {
        $app = new WebApplication(__DIR__."/app", "/testpage");

        $other = new Loops(new ArrayObject);
        $loops = $app->getLoops();

        $this->assertNotSame($other, $loops);

        $page = new Testpage([], $other);

        $this->assertSame($other, $page->getLoops());
    }

    public function testIsPage() {
        $app = new WebApplication(__DIR__."/app", "/testpage");

        $page = new Testpage;

        $this->assertTrue($page->isPage());
    }

    public function testDirectAccess() {
        $app = new WebApplication(__DIR__."/app", "/testpage");
        ob_start();
        $app->run();
        ob_end_clean();

        $this->assertInstanceOf("Pages\Testpage", $app->getLoops()->getService("web_core")->page);
    }

    public function testLoopsId() {
        $app = new WebApplication(__DIR__."/app", "/testpage");
        $loops = $app->getLoops();

        $page = new Testpage;

        $this->assertSame("Pages-Testpage", $page->getLoopsId());
    }

    public function testLoopsIdDeclaringPage() {
        $app = new WebApplication(__DIR__."/app", "/testpage");
        $loops = $app->getLoops();

        $page = new Subtestpage;

        $this->assertInstanceOf("DummyElement", $page->test);
        $this->assertInstanceOf("DummyElement", $page->test2);

        $this->assertSame("Pages-Testpage-test", $page->test->getLoopsId());
        $this->assertSame("Pages-Subtestpage-test2", $page->test2->getLoopsId());
    }

    public function testLoopsIdWithPageParameter() {
        $app = new WebApplication(__DIR__."/app", "/testpage");
        $loops = $app->getLoops();

        $page = new Parampage(["a"]);
        $this->assertSame("Pages-a-Parampage", $page->getLoopsId());

        $page = new Parampage(["test"]);
        $this->assertSame("Pages-test-Parampage", $page->getLoopsId());

        $page = new _(["a", "test"]);
        $this->assertSame("Pages-a-Deeper-test", $page->getLoopsId());
    }

    public function testPagePath() {
        $app = new WebApplication(__DIR__."/app", "/");
        $loops = $app->getLoops();

        $page = new Testpage([], $loops);

        $this->assertSame("testpage", $page->getPagePath());
    }

    public function testPagePathWithPageParameter() {
        $app = new WebApplication(__DIR__."/app", "/");
        $loops = $app->getLoops();

        $page = new Parampage(["a"]);
        $this->assertSame("a/parampage", $page->getPagePath());

        $page = new Parampage(["test"]);
        $this->assertSame("test/parampage", $page->getPagePath());

        $page = new _(["a", "test"]);
        $this->assertSame("a/deeper/test", $page->getPagePath());
    }

    public function testIndexPagePage() {
        $app = new WebApplication(__DIR__."/app", "/");
        $loops = $app->getLoops();

        $page = new Index(["a"]);
        $this->assertSame("a/deeper/", $page->getPagePath());
    }

    public function testInvalidPage() {
        $app = new WebApplication(__DIR__."/app", "/testpage");
        $loops = $app->getLoops();

        $page = new InvalidPage($loops);

        $this->assertFalse($page->getPagePath());
    }
}
