<?php

use Loops\ArrayObject;

$c["test"] = "test";
$c["session"]["plugin"] = "Test";

$c["config_test_service"]["a"]     = "c";
$c["config_test_service"]["other"] = "other";

// keep quiet (stderr logging is enabled on default)
$c["logger"]["plugin"] = "";

return ArrayObject::fromArray($c);
