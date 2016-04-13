<?php

class DummyClass {
    public $param1;
    public $param2;
    public $param3;
    
    public function __construct($param1 = TRUE, $param2 = 1, $param3 = "test") {
        $this->param1 = $param1;
        $this->param2 = $param2;
        $this->param3 = $param3;
    }
}