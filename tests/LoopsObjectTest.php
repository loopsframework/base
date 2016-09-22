<?php

require_once(__DIR__."/LoopsTestCase.php");

use Loops\ArrayObject;
use Loops\Application\WebApplication;

class Mock extends Loops\Object {
    public $param1 = 1;
    public $param2 = "2";
}

class LoopsObjectTest extends LoopsTestCase {
    public function testLoopsGetter() {
        $loops = new Loops(new ArrayObject);
        $object = new Mock($loops);

        $this->assertSame($loops, $object->loops);
        $this->assertSame($loops, $object->getLoops());
    }

    public function testAccessService() {
        $loops = new Loops(new ArrayObject);
        $object = new Mock($loops);

        $this->assertSame($loops->getService('web_core'), $object->web_core);
    }

    public function testNaValueNotice() {
        $this->setExpectedException("PHPUnit_Framework_Error_Notice");
        $loops = new Loops(new ArrayObject);
        $object = new Mock($loops);
        $object->non_existing_value;
    }

    public function testGetterShortcut() {
        $loops = new Loops(new ArrayObject);
        $object = new Mock($loops);

        $this->assertSame($object->offsetGet('web_core'), $object->_('web_core'));
    }
}
