<?php

require_once(__DIR__."/LoopsTestCase.php");

use Loops\ArrayObject;
use Loops\Misc\EventTrait;
use Loops\Annotations\Listen;

class EventsTestMock1 {
    use EventTrait;
}

class EventsTestMock2 {
    use EventTrait;

    /**
     * @Listen("onTest")
     */
    public $other = [ __CLASS__, "test2" ];

    /**
     * @Listen("onTest")
     */
    public function test() {
        echo "ok";
        return FALSE;
    }

    public function test2() {
        echo "OK";
        return TRUE;
    }

    /**
     * @Listen("onTest2")
     */
    public function test3() {
        echo "ok";
        return TRUE;
    }
}

class EventsTestMock2Silent {
    use EventTrait;

    /**
     * @Listen("onTest")
     */
    public $other = [ __CLASS__, "test2" ];

    /**
     * @Listen("onTest")
     */
    public function test() {
        return FALSE;
    }

    public function test2() {
        return TRUE;
    }

    /**
     * @Listen("onTest2")
     */
    public function test3() {
        return TRUE;
    }
}

class EventsTestMock3 {
    use EventTrait;

    /**
     * @Listen("onTest")
     */
    public function test() {
    }
}

class EventsTestMock4 {
    use EventTrait;

    /**
     * @Listen("onTest")
     */
    public $other = [ __CLASS__, "test2" ];

    /**
     * @Listen("onTest")
     */
    public function test() {
        return TRUE;
    }

    public function test2() {
        return TRUE;
    }
}

class EventsTestMock5 {
    use EventTrait;

    public $param1;
    public $param2;

    /**
     * @Listen("onTest")
     */
    public function test($param1, $param2) {
        $this->param1 = $param1;
        $this->param2 = $param2;
    }
}

class LoopsMiscEventsTraitTest extends LoopsTestCase {
    public function testCustomListerner() {
        $loops = new Loops(new ArrayObject);

        $object = new EventsTestMock1;
        $object->addListener("onTest", function() { echo "ok"; });
        $object->addListener("onTest", function() { echo "OK"; });

        $this->expectOutputString("okOK");
        $object->fireEvent("onTest");
    }

    public function testAnnotatedEvents() {
        $loops = new Loops(new ArrayObject);

        $object = new EventsTestMock2;

        $this->expectOutputString("okOK");
        $result = $object->fireEvent("onTest");

        $this->assertSame([FALSE,TRUE],$result);
    }

    public function testAggregates() {
        $loops = new Loops(new ArrayObject);

        $object = new EventsTestMock1;

        //no listener -> default to true
        $result = $object->fireEvent("onTest", [], TRUE);
        $this->assertTrue($result);

        //no listener -> do not default to true
        $result = $object->fireEvent("onTest", [], TRUE, TRUE, FALSE);
        $this->assertFalse($result);

        //listener with [TRUE, FALSE] -> aggregate must be false
        $object = new EventsTestMock2Silent;
        $result = $object->fireEvent("onTest", [], TRUE);
        $this->assertFalse($result);

        //listener with [NULL] -> default NULL to true
        $object = new EventsTestMock3;
        $result = $object->fireEvent("onTest", [], TRUE);
        $this->assertTrue($result);

        //listener with [NULL] -> do not default NULL to true
        $object = new EventsTestMock3;
        $result = $object->fireEvent("onTest", [], TRUE, FALSE);
        $this->assertFalse($result);

        //listener with [TRUE, TRUE] -> aggregate to TRUE
        $object = new EventsTestMock4;
        $result = $object->fireEvent("onTest", [], TRUE, FALSE);
        $this->assertTrue($result);
    }

    public function testParams() {
        $loops = new Loops(new ArrayObject);

        $object = new EventsTestMock5;
        $object->fireEvent("onTest", [1,2]);
        $this->assertSame(1, $object->param1);
        $this->assertSame(2, $object->param2);
    }

    public function testBinding() {
        $loops = new Loops(new ArrayObject);

        $object1 = new EventsTestMock1;
        $object2 = new EventsTestMock2;

        $object1->bindEventObject($object2);

        $this->expectOutputString("okOKok");
        $object1->fireEvent("onTest");
        $object1->fireEvent("onTest2");
    }

    public function testBindingFilter() {
        $loops = new Loops(new ArrayObject);

        $object1 = new EventsTestMock1;
        $object2 = new EventsTestMock2;

        $object1->bindEventObject($object2, "onTest");

        $this->expectOutputString("okOK");
        $object1->fireEvent("onTest");
        $object1->fireEvent("onTest2");
    }
}
