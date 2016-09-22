<?php

require_once(__DIR__."/LoopsTestCase.php");

use Loops\ArrayObject;

class LoopsArrayObjectTest extends LoopsTestCase {
    public function testDefaultFlag() {
        $arrayobject = new ArrayObject;
        $arrayobject->test = "test";

        $this->assertSame($arrayobject["test"], $arrayobject->test);
    }

    public function testToArray() {
        $test["a"] = 1;

        $arrayobject = new ArrayObject($test);
        $this->assertSame($arrayobject->toArray(), [ "a" => 1 ]);

        $test["b"] = new ArrayObject([ "c" => 2 ]);

        $arrayobject = new ArrayObject($test);
        $this->assertSame($arrayobject->toArray(), [ "a" => 1, "b" => [ "c" => 2 ] ]);
    }

    public function testMerge() {
        //test simple level merge
        $test1["a"] = 1;
        $test2["b"] = 2;
        $test3["b"] = 3;

        $arrayobject1 = new ArrayObject($test1);
        $arrayobject2 = new ArrayObject($test2);
        $arrayobject3 = new ArrayObject($test3);

        $res = $arrayobject1->merge($arrayobject2);

        $this->assertSame($arrayobject1, $res);
        $this->assertSame($arrayobject1->a, 1);
        $this->assertSame($arrayobject1->b, 2);

        //test overwrite
        $arrayobject1->merge($arrayobject3);
        $this->assertSame($arrayobject1->b, 3);

        $test1["a"] = 1;
        $test2["b"] = new ArrayObject([ "c" => 2 ]);
        $test3["b"] = new ArrayObject([ "c" => 3, "d" => 4 ]);

        //do the same on multi level arrays
        $arrayobject1 = new ArrayObject($test1);
        $arrayobject2 = new ArrayObject($test2);
        $arrayobject3 = new ArrayObject($test3);

        $arrayobject1->merge($arrayobject2);

        $this->assertSame($arrayobject1->a, 1);
        $this->assertSame($arrayobject1->b->c, 2);

        $arrayobject1->merge($arrayobject3);

        $this->assertSame($arrayobject1->b->c, 3);
        $this->assertSame($arrayobject1->b->d, 4);
    }

    public function testFromArray() {
        $array = ArrayObject::fromArray([ "a" => "b", "c" => 4 ]);

        $this->assertInstanceOf("Loops\ArrayObject", $array);
        $this->assertSame("b", $array["a"]);
        $this->assertSame(4, $array["c"]);
    }

    public function testFromArrayNested() {
        $array = ArrayObject::fromArray([ "a" => "b", "c" => [ "d" => "e" ] ]);

        $this->assertInstanceOf("Loops\ArrayObject", $array);
        $this->assertInstanceOf("Loops\ArrayObject", $array["c"]);
        $this->assertSame("e", $array["c"]["d"]);
    }
}
