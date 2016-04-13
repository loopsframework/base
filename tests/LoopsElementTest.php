<?php

require_once(__DIR__."/LoopsTestCase.php");

use Loops\ArrayObject;
use Loops\Element;
use Loops\Page;
use Loops\Annotations\Object;
use Loops\Application\WebApplication;
use Pages\Testpage;
use Pages\_\Deeper\Index;
use Pages\_\DeeperDelegate\Index as IndexDelegate;

class DefaultPage extends Page {
}

class DefaultElement extends Element {
}

class ElementMock1a extends Element {
    /**
     * @Object("AcceptingElement")
     */
    protected $test;
}

class ElementMock1b extends Element {
    protected $delegate_action = "acc";
    
    /**
     * @Object("AcceptingElement")
     */
    protected $acc;
}

class ElementMock2a extends Element {
    protected $direct_access = FALSE;
}

class ElementMock2b extends Element {
    protected $direct_access = TRUE;
}

class ElementMock3a extends Element {
    protected $ajax_access = FALSE;
}

class ElementMock3b extends Element {
    protected $ajax_access = TRUE;
}

class ElementMock4 extends Element {
    protected $cache_lifetime = 10;
}

class ElementMock5 extends Element {
    protected $cache_lifetime = 0;
}

class ElementMock6 extends Element {
    public function testInit($offset, $element, $detach = FALSE) {
        return $this->initChild($offset, $element, $detach);
    }
}

class ElementMock7 extends Element {
    /**
     * @Object("AcceptingElement")
     */
    protected $test;
    
    public function testGetGenerator($include_readonly = FALSE, $include_protected = FALSE, $include = FALSE, $exclude = FALSE) {
        return $this->getGenerator($include_readonly, $include_protected, $include, $exclude);
    }
}

class ElementMock8 extends Element {
    public function testAction() {
        return TRUE;
    }
}

class AcceptingElement extends Element {
    public $leftover = [];
    public function action($parameter) {
        $this->leftover = $parameter;
        return $this;
    }
}

class LoopsElementTest extends LoopsTestCase {
    public function testContext() {
        $loops = new Loops(new ArrayObject);
        
        $element = new DefaultElement($this, $loops);
        $this->assertSame($element->context, $this);
    }
    
    public function testEmptyContext() {
        $loops = new Loops(new ArrayObject);
        
        $element = new DefaultElement;
        $this->assertNull($element->context);
    }
    
    public function testDelegateAction() {
        $loops = new Loops(new ArrayObject);
        
        $element = new ElementMock1a;
        $this->assertFalse($element->action(["abc"]));
        
        $element = new ElementMock1b;
        $this->assertSame($element->acc, $element->action(["abc"]));
        $this->assertSame(["abc"], $element->acc->leftover);
    }
    
    public function testDirectAccess() {
        $loops = new Loops(new ArrayObject);
        
        $element = new ElementMock2a;
        $this->assertFalse($element->action([]));
        
        $element = new ElementMock2b;
        $this->assertSame($element, $element->action([]));
    }
    
    public function testAjaxAccess() {
        $app = new WebApplication(__DIR__."/app", "/", "GET", [], [], [], [], [], FALSE);
        
        $element = new ElementMock2a;
        $this->assertFalse($element->action([]));
        
        $app = new WebApplication(__DIR__."/app", "/", "GET", [], [], [], [], [], TRUE);
        
        $element = new ElementMock2b;
        $this->assertSame($element, $element->action([]));
    }
    
    public function testCacheLifetime() {
        $loops = new Loops(new ArrayObject);
        
        $element = new DefaultElement;
        $this->assertSame(-1, $element->getCacheLifetime());
        
        $element = new ElementMock4;
        $this->assertSame(10, $element->getCacheLifetime());
    }
    
    public function testGetMagicLoopsId() {
        $loops = new Loops(new ArrayObject);
        
        $element = new DefaultElement;
        
        $this->assertSame($element->loopsid, $element->getLoopsId());
    }
    
    public function testGetMagicPagePath() {
        $loops = new Loops(new ArrayObject);
        
        $element = new DefaultElement;
        
        $this->assertSame($element->pagepath, $element->getPagePath());
        
        $page = new DefaultPage;
        
    }
    
    public function testLoopsIdIsolated() {
        $loops = new Loops(new ArrayObject);
        
        $element = new DefaultElement;
        
        $this->assertSame(spl_object_hash($element), $element->getLoopsId());
    }
    
    public function testLoopsIdChild() {
        $loops = new Loops(new ArrayObject);
        
        $element = new DefaultElement;
        $element->test = new DefaultElement;
        
        $this->assertSame($element->getLoopsId()."-test", $element->test->getLoopsId());
    }
    
    public function testLoopsCacheable() {
        $loops = new Loops(new ArrayObject);
        
        $element = new DefaultElement;
        
        $this->assertFalse($element->isCacheable());
    }
    
    public function testLoopsCacheableExpiringCache() {
        $loops = new Loops(new ArrayObject);
        
        $element = new ElementMock4;
        
        $this->assertTrue($element->isCacheable());
    }
    
    public function testLoopsCacheableNonExpiringCache() {
        $loops = new Loops(new ArrayObject);
        
        $element = new ElementMock5;
        
        $this->assertTrue($element->isCacheable());
    }
    
    public function testLoopsCacheid() {
        $loops = new Loops(new ArrayObject);
        
        $element = new DefaultElement;
        
        $this->assertSame($element->getLoopsId(), $element->getCacheId());
    }
    
    public function testLoopsCacheLifetime() {
        $loops = new Loops(new ArrayObject);
        
        $element = new ElementMock4;
        
        $this->assertTrue($element->isCacheable());
        $this->assertSame(10, $element->getCacheLifetime());
    }
    
    public function testLoopsCacheLifetimeNonExpiringCache() {
        $loops = new Loops(new ArrayObject);
        
        $element = new ElementMock5;
        
        $this->assertTrue($element->isCacheable());
        $this->assertSame(0, $element->getCacheLifetime());
    }
    
    public function testLoopsAddChild() {
        $loops = new Loops(new ArrayObject);
        
        $element1 = new DefaultElement;
        $element2 = new DefaultElement;
        
        $element1->addChild("test", $element2);
        
        $this->assertSame($element2, $element1->offsetGet("test"));
    }
    
    public function testLoopsInitChild() {
        $loops = new Loops(new ArrayObject);
        
        $element1 = new ElementMock6;
        $element2 = new DefaultElement;
        $result = $element1->testInit("test", $element2);
        
        $this->assertSame($element2, $result);
        $this->assertSame("test", $element2->getName());
        $this->assertSame($element1, $element2->getParent());
    }
    
    public function testLoopsInitChildAlreadyInitialized() {
        $loops = new Loops(new ArrayObject);
        
        $element1 = new ElementMock6;
        $element2 = new ElementMock6;
        $element3 = new DefaultElement;

        $result = $element1->testInit("test1", $element3);
        
        $this->assertSame("test1", $element3->getName());
        $this->assertSame($element1, $element3->getParent());
        
        $result = $element2->testInit("test2", $element3);
        
        $this->assertNotSame("test2", $element3->getName());
        $this->assertNotSame($element2, $element3->getParent());
    }
    
    public function testLoopsInitChildNonElement() {
        $loops = new Loops(new ArrayObject);
        
        $element1 = new ElementMock6;
        $element2 = new DateTime;
        $result = $element1->testInit("test", $element2);
        
        $this->assertSame($element2, $result);
    }
    
    public function testLoopsInitChildAutoDetach() {
        $loops = new Loops(new ArrayObject);
        
        $element1 = new ElementMock6;
        $element2 = new ElementMock6;
        $element3 = new DefaultElement;

        $result = $element1->testInit("test1", $element3);
        
        $this->assertSame("test1", $element3->getName());
        $this->assertSame($element1, $element3->getParent());
        
        $result = $element2->testInit("test2", $element3, TRUE);
        
        $this->assertSame("test2", $element3->getName());
        $this->assertSame($element2, $element3->getParent());
        $this->setExpectedException("PHPUnit_Framework_Error_Notice");
        $element1->offsetGet("test1");
    }
    
    public function testLoopsAutoInitGet() {
        $loops = new Loops(new ArrayObject);
        
        $element = new ElementMock1a;
        
        $this->assertSame("test", $element->test->getName());
        $this->assertSame($element, $element->test->getParent());
    }
    
    public function testLoopsAutoInitSet() {
        $loops = new Loops(new ArrayObject);
        
        $element1 = new DefaultElement;
        $element2 = new DefaultElement;
        $element3 = new DefaultElement;

        $element1->offsetSet("test1", $element3);
        
        $this->assertSame("test1", $element3->getName());
        $this->assertSame($element1, $element3->getParent());
        
        $element2->offsetSet("test2", $element3);
        
        $this->assertSame("test2", $element3->getName());
        $this->assertSame($element2, $element3->getParent());
        
        $this->setExpectedException("PHPUnit_Framework_Error_Notice");
        $element1->offsetGet("test1");
    }
    
    public function testLoopsAutoDetach() {
        $loops = new Loops(new ArrayObject);
        
        $element1       = new DefaultElement;
        $element2       = new DefaultElement;
        $element1->offsetSet("test", $element2);
        
        $this->assertSame("test", $element2->getName());
        $this->assertSame($element1, $element2->getParent());
        
        $element1->offsetUnset("test");
        
        $this->assertFalse($element2->getName());
        $this->assertFalse($element2->getParent());
        
        $this->setExpectedException("PHPUnit_Framework_Error_Notice");
        $element1->offsetGet("test");
    }
    
    public function testLoopsGeneratorAutoInit() {
        $loops = new Loops(new ArrayObject);
        
        $element1 = new ElementMock7;
        
        foreach($element1->testGetGenerator(TRUE, TRUE) as $key => $value) {
            if($value instanceof Element) {
                $this->assertSame($key, $value->getName());
                $this->assertSame($element1, $value->getParent());
            }
        }
    }
    
    public function testLoopsActionDefault() {
        //Note: other action requests were already tested
        $loops = new Loops(new ArrayObject);
        
        $element = new DefaultElement;
        
        $this->assertFalse($element->action([]));
        $this->assertFalse($element->action(["a"]));
        $this->assertFalse($element->action(["a","test"]));
    }
    
    public function testLoopsActionMethod() {
        $loops = new Loops(new ArrayObject);
        
        $element = new ElementMock8;
        
        $this->assertSame($element, $element->action(["test"]));
    }
    
    public function testLoopsActionSubElement() {
        $loops = new Loops(new ArrayObject);
        
        $element1 = new DefaultElement;
        $element2 = new AcceptingElement;
        
        $element1->offsetSet("test", $element2);
        
        $this->assertSame($element2, $element1->action(["test"]));
        
        $this->assertSame($element2, $element1->action(["test", "a", "b"]));
        $this->assertSame(["a", "b"], $element2->leftover);
    }
    
    public function testLoopsDetach() {
        $loops = new Loops(new ArrayObject);
        
        $element1       = new DefaultElement;
        $element2       = new DefaultElement;
        $element1->offsetSet("test", $element2);
        
        $this->assertSame("test", $element2->getName());
        $this->assertSame($element1, $element2->getParent());
        
        $element2->detach();
        
        $this->assertFalse($element2->getName());
        $this->assertFalse($element2->getParent());
        
        $this->setExpectedException("PHPUnit_Framework_Error_Notice");
        $element1->offsetGet("test");
    }
    
    public function testLoopsGetParent() {
        $loops = new Loops(new ArrayObject);
        
        $element1       = new DefaultElement;
        $element2       = new DefaultElement;
        
        $this->assertFalse($element2->getParent());
        
        $element1->offsetSet("test", $element2);
        
        $this->assertSame($element1, $element2->getParent());
    }
    
    public function testLoopsGetName() {
        $loops = new Loops(new ArrayObject);
        
        $element1       = new DefaultElement;
        $element2       = new DefaultElement;
        
        $this->assertFalse($element2->getName());
        
        $element1->offsetSet("test", $element2);
        
        $this->assertSame("test", $element2->getName());
    }
    
    public function testLoopsGetPagePathNotInPage() {
        $app = new WebApplication(__DIR__."/app", "/", "GET", [], [], [], [], [], FALSE);
        $loops = $app->getLoops();
        
        $element = new DefaultElement;
        
        $this->assertFalse($element->getPagePath());
    }
    
    public function testLoopsGetPagePathInvalidPage() {
        $app = new WebApplication(__DIR__."/app", "/", "GET", [], [], [], [], [], FALSE);
        $loops = $app->getLoops();
        
        $page = new DefaultPage;
        $element = new DefaultElement;
        
        $page->offsetSet("test", $element);
        
        $this->assertFalse($element->getPagePath());
    }
    
    public function testLoopsGetPagePathOneElement() {
        $app = new WebApplication(__DIR__."/app", "/", "GET", [], [], [], [], [], FALSE);
        $loops = $app->getLoops();
        
        $page = new Testpage;
        $element = new DefaultElement;
        
        $page->offsetSet("aaa", $element);
        
        $this->assertSame("testpage/aaa", $element->getPagePath());
    }
    
    public function testLoopsGetPagePathDeeper() {
        $app = new WebApplication(__DIR__."/app", "/", "GET", [], [], [], [], [], FALSE);
        $loops = $app->getLoops();
        
        $page = new Testpage;
        $element = new DefaultElement;
        $element2 = new DefaultElement;
        $element->offsetSet("bbb", $element2);
        
        $page->offsetSet("aaa", $element);
        
        $this->assertSame("testpage/aaa/bbb", $element2->getPagePath());
    }
    
    public function testLoopsGetPagePathDelegate() {
        $app = new WebApplication(__DIR__."/app", "/", "GET", [], [], [], [], [], FALSE);
        $loops = $app->getLoops();
        
        $page = new Testpage;
        $element = new ElementMock1b;
        
        $page->offsetSet("aaa", $element);
        
        $this->assertSame("testpage/aaa", $element->offsetGet("acc")->getPagePath());
    }
    
    public function testLoopsGetPagePathIndex() {
        $app = new WebApplication(__DIR__."/app", "/", "GET", [], [], [], [], [], FALSE);
        $loops = $app->getLoops();
        
        $page = new Index(["a"]);
        $element = new DefaultElement;
        
        $page->offsetSet("aaa", $element);
        
        $this->assertSame("a/deeper/aaa", $element->getPagePath());
    }
    
    public function testLoopsGetPagePathIndexDelegate() {
        $app = new WebApplication(__DIR__."/app", "/", "GET", [], [], [], [], [], FALSE);
        $loops = $app->getLoops();
        
        $page = new IndexDelegate(["a"]);
        $element = new DefaultElement;
        
        $page->offsetSet("test", $element);
        
        $this->assertSame("a/deeperdelegate/", $element->getPagePath());
    }
    
    public function testIsNotPage() {
        $this->assertFalse(Element::isPage());
    }
}
