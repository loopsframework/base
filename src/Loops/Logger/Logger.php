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

namespace Loops\Logger;

use Loops;
use Loops\ArrayObject;
use Loops\Object;
use Loops\Messages\Message;

abstract class Logger extends Object implements LoggerInterface {
    protected $severity = Message::WARNING;
    
    protected $stderr_logger;
    
    public function __construct($severity = "NOTICE", $stderr_logging_cli = TRUE, Loops $loops = NULL) {
        parent::__construct($loops);
        
        $this->severity = constant("Loops\Messages\Message::$severity");

        if($stderr_logging_cli && php_sapi_name() == "cli") {
            $this->stderr_logger = $this->getLoops()->createService("logger", ArrayObject::fromArray(["plugin" => "Stderr"]), TRUE);
        }
    }
    
    public function log(Message $message) {
        if($message->severity > $this->severity) {
            return NULL;
        }
        
        if($this->stderr_logger) {
            $this->stderr_logger->log($message);
        }
        
        return $this->__log($message);
    }
    
    abstract protected function __log(Message $message);
    
    protected function line(Message $message) {
        return "[ ".date("r")." | ".str_pad($message->getSeverityName(), 7)." ] ".$message->message;
    }
    
    public function __get($key) {
        switch($key) {
            case 'emerg':   return $this->severity >= Message::EMERG;
            case 'alert':   return $this->severity >= Message::ALERT;
            case 'crit':    return $this->severity >= Message::CRIT;
            case 'err':     return $this->severity >= Message::ERR;
            case 'warning': return $this->severity >= Message::WARNING;
            case 'notice':  return $this->severity >= Message::NOTICE;
            case 'info':    return $this->severity >= Message::INFO;
            case 'debug':   return $this->severity >= Message::DEBUG;
        }
    }
    
    public function setSeverity($severity) {
        $this->severity = $severity;
    }
    
    public function getSeverity() {
        return $this->severity;
    }
    
    public function emerg($message) {
        return $this->log(new Message($message, Message::EMERG));
    }
    
    public function alert($message) {
        return $this->log(new Message($message, Message::ALERT));
    }
    
    public function crit($message) {
        return $this->log(new Message($message, Message::CRIT));
    }
    
    public function err($message) {
        return $this->log(new Message($message, Message::ERR));
    }
    
    public function warning($message) {
        return $this->log(new Message($message, Message::WARNING));
    }
    
    public function notice($message) {
        return $this->log(new Message($message, Message::NOTICE));
    }
    
    public function info($message) {
        return $this->log(new Message($message, Message::INFO));
    }
    
    public function debug($message) {
        return $this->log(new Message($message, Message::DEBUG));
    }
}