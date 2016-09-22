<?php

require_once(__DIR__."/LoopsTestCase.php");

use Loops\Misc\AccessTrait;
use Loops\Annotations\Access\ReadOnly;
use Loops\Annotations\Access\ReadWrite;
use Loops\Annotations\Access\Expose;
use Loops\Annotations\Access\Sleep;

class AccessTestMockExpose0 implements IteratorAggregate {
    use AccessTrait;
}

class AccessTestMockExpose1 implements IteratorAggregate {
    use AccessTrait;

    protected $test1 = "test1";
}

class AccessTestMockExpose2 implements IteratorAggregate {
    use AccessTrait;

    /**
     * @Expose
     */
    protected $test2 = "test2";
}

class AccessTestMockExpose3 implements IteratorAggregate {
    use AccessTrait;

    public $test = "test";

    /**
     * @Expose
     */
    protected $test3 = "test3";
}

class AccessTestMockExpose4 implements IteratorAggregate {
    use AccessTrait;

    public $test4 = "test4";
}

class AccessTestMockReadOnly {
    use AccessTrait;

    /**
     * @ReadOnly
     */
    protected $rovalue = "test";
}

class AccessTestMockReadOnlyGetter {
    use AccessTrait;

    /**
     * @ReadOnly("getRo")
     */
    protected $rovalue;

    public function getRo() {
        return "test2";
    }
}

class AccessTestMockReadWrite {
    use AccessTrait;

    /**
     * @ReadWrite
     */
    protected $rwvalue = "test3";
}

class AccessTestMockReadWriteGetterSetter {
    use AccessTrait;

    /**
     * @ReadWrite("setRw",getter="getRw")
     */
    protected $rwvalue;

    public function getRw() {
        return $this->rwvalue."test4";
    }

    public function setRw($value) {
        $this->rwvalue = $value."test5";
    }
}

class LoopsMiscAccessTraitTest extends LoopsTestCase {
    public function testInvalidAccess() {
        $this->setExpectedException("PHPUnit_Framework_Error_Notice");
        $object = new AccessTestMockExpose0;
        $object->offsetGet("non_existing_value");
    }

    public function testExposeNone() {
        $object = new AccessTestMockExpose0;

        $result = [];

        foreach($object as $key => $value) {
            $result[$key] = $value;
        }

        $this->assertSame([], $result);
    }

    public function testExposePublic() {
        $object = new AccessTestMockExpose4;

        $result = [];

        foreach($object as $key => $value) {
            $result[$key] = $value;
        }

        $this->assertSame(["test4" => "test4"], $result);
    }

    public function testExposeNoAnnotation() {
        $object = new AccessTestMockExpose1;

        $result = [];

        foreach($object as $key => $value) {
            $result[$key] = $value;
        }

        $this->assertSame([], $result);
    }

    public function testExpose() {
        $object = new AccessTestMockExpose2;

        $result = [];

        foreach($object as $key => $value) {
            $result[$key] = $value;
        }

        $this->assertSame(["test2"=>"test2"], $result);
    }

    public function testExposeMixed() {
        $object = new AccessTestMockExpose3;

        $result = [];

        foreach($object as $key => $value) {
            $result[$key] = $value;
        }

        $this->assertSame(["test"=>"test","test3"=>"test3"], $result);
    }

    public function testReadOnly() {
        $object = new AccessTestMockReadOnly;
        $this->assertEquals("test", $object->offsetGet("rovalue"));
    }

    public function testReadOnlyException() {
        $this->setExpectedException("Loops\Exception");
        $object = new AccessTestMockReadOnly;
        $object->offsetSet("rovalue", "except");
    }

    public function testReadOnlyGetter() {
        $object = new AccessTestMockReadOnlyGetter;
        $this->assertEquals("test2", $object->offsetGet("rovalue"));
    }

    public function testReadWrite() {
        $uid = uniqid();
        $object = new AccessTestMockReadWrite;
        $object->offsetSet("rwvalue", $uid);
        $this->assertEquals($uid, $object->offsetGet("rwvalue"));
    }

    public function testReadWriteGetterSetter() {
        $uid = uniqid();
        $object = new AccessTestMockReadWriteGetterSetter;
        $object->offsetSet("rwvalue", $uid);
        $this->assertEquals($uid."test5test4", $object->offsetGet("rwvalue"));
    }
}
