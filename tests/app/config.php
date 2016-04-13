<?php

use Loops\ArrayObject;

$c["test"] = "test";
$c["session"]["plugin"] = "Test";

$c["config_test_service"]["a"]     = "c";
$c["config_test_service"]["other"] = "other";

return ArrayObject::fromArray($c);