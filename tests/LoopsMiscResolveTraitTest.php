<?php

require_once(__DIR__."/LoopsTestCase.php");

use Loops\ArrayObject;
use Loops\Annotations\Object;
use Loops\Misc\ResolveTrait;
use Loops\Object as LoopsObject;

class ResolveMockObject {
    public $context;
    public $loops;

    public function __construct($context = NULL, $loops = NULL) {
        $this->context = $context;
        $this->loops = $loops;
    }
}

class ResolveMock1Public implements ArrayAccess {
    use ResolveTrait;

    /**
     * @Object("ResolveMockObject")
     */
    public $mock;

    public $no_mock;
}

class ResolveMock1Protected implements ArrayAccess {
    use ResolveTrait;

    /**
     * @Object("ResolveMockObject")
     */
    public $mock;

    public $no_mock;

    public function __get($key) {
        return $this->offsetGet($key);
    }
}

class ResolveMock2 implements ArrayAccess {
    use ResolveTrait;

    /**
     * @Object("ResolveMockObject")
     */
    public $test;
}

class ResolveMock3 extends LoopsObject implements ArrayAccess {
    use ResolveTrait;

    /**
     * @Object("ResolveMockObject")
     */
    public $test;
}


class LoopsMiscResolveTraitTest extends LoopsTestCase {
    public function testOffsetExists() {
        $object1 = new ResolveMock1Public;
        $object2 = new ResolveMock1Protected;

        $this->assertTrue($object1->offsetExists("mock"));
        $this->assertFalse($object1->offsetExists("no_mock"));

        $this->assertTrue($object2->offsetExists("mock"));
        $this->assertFalse($object2->offsetExists("no_mock"));
    }

    public function testOffsetGet() {
        $object1 = new ResolveMock1Public;
        $object2 = new ResolveMock1Protected;

        $this->assertInstanceOf("ResolveMockObject", $object1->offsetGet("mock"));
        $this->assertInstanceOf("ResolveMockObject", $object2->offsetGet("mock"));

        //bonus tests
        $this->assertInstanceOf("ResolveMockObject", $object1["mock"]);
        $this->assertInstanceOf("ResolveMockObject", $object2["mock"]);
        $this->assertInstanceOf("ResolveMockObject", $object2->mock);
    }

    public function testOffsetGetSameObject() {
        $object1 = new ResolveMock1Public;
        $this->assertSame($object1->offsetGet("mock"), $object1->offsetGet("mock"));
    }

    public function testOffsetGetNotice() {
        $object = new ResolveMock1Public;

        $this->setExpectedException("PHPUnit_Framework_Error_Notice");
        $object->offsetGet("non_existend_property");

        $this->setExpectedException("PHPUnit_Framework_Error_Notice");
        $object->offsetGet("no_date");
    }

    public function testOffsetGetInstanciateArgs() {
        $loops = new Loops(new ArrayObject);

        $object = new ResolveMock2;

        $resolved = $object["test"];

        $this->assertInstanceOf("ResolveMockObject", $resolved);
        $this->assertSame($object, $resolved->context);
        $this->assertSame($loops, $resolved->loops);
    }

    public function testOffsetGetInstanciateArgsLoops() {
        $loops = new Loops(new ArrayObject);

        $object = new ResolveMock3($loops);

        $newloops = new Loops(new ArrayObject);

        $resolved = $object["test"];

        $this->assertInstanceOf("ResolveMockObject", $resolved);
        $this->assertSame($loops, $resolved->loops);
    }

    public function testOffsetSet() {
        $object = new ResolveMock1Public;

        $this->setExpectedException("Loops\Exception");
        $object->offsetSet("mock", new ResolveMockObject);
    }

    public function testOffsetUnset() {
        $object = new ResolveMock1Public;

        $date1 = $object->offsetGet("mock");

        $object->offsetUnset("mock");

        $date2 = $object->offsetGet("mock");

        $this->assertInstanceOf("ResolveMockObject", $date1);
        $this->assertInstanceOf("ResolveMockObject", $date2);
        $this->assertNotSame($date1, $date2);
    }
}
