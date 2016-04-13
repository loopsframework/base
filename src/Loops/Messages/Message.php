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

class Message {
    const EMERG   = 0;
    const ALERT   = 1;
    const CRIT    = 2;
    const ERR     = 3;
    const WARNING = 4;
    const NOTICE  = 5;
    const INFO    = 6;
    const DEBUG   = 7;
    
    public $message;
    public $severity;
    
    public function __construct($message, $severity = Message::INFO) {
        $this->message = $message;
        $this->severity = $severity;
    }
    
    public function getSeverityName() {
        $names = [ "EMERG", "ALERT", "CRIT", "ERR", "WARNING", "NOTICE", "INFO", "DEBUG" ];
        return array_key_exists($this->severity, $names) ? $names[$this->severity] : "unknown";
    }
}