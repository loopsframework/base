<?php
/**
 * This file is part of the Loops framework.
 *
 * @author Lukas <lukas@loopsframework.com>
 * @license https://raw.githubusercontent.com/loopsframework/base/master/LICENSE
 * @link https://github.com/loopsframework/base
 * @link https://loopsframework.com/
 * @version 0.1
 */

namespace Loops\Messages;

use DateTime;

class Message {
    const EMERGENCY = 0;
    const ALERT     = 1;
    const CRITICAL  = 2;
    const ERROR     = 3;
    const WARNING   = 4;
    const NOTICE    = 5;
    const INFO      = 6;
    const DEBUG     = 7;

    public $message;
    public $severity;
    public $time;

    public function __construct($message, $severity = Message::INFO) {
        $this->message = $message;
        $this->severity = $severity;
        $this->time = new DateTime;
    }

    public function getSeverityName() {
        $names = [ "EMERGENCY", "ALERT", "CRITICAL", "ERROR", "WARNING", "NOTICE", "INFO", "DEBUG" ];
        return array_key_exists($this->severity, $names) ? $names[$this->severity] : "unknown";
    }

    public function __toString() {
        return "[".$this->time->format("r")."|".str_pad($this->getSeverityName(), 9)."] ".$this->message;
    }
}
