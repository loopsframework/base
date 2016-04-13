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
use Loops\Messages\Message;

class StderrLogger extends Logger {
    public $color = TRUE;
    
    public function __construct($severity = "NOTICE", $color = NULL, Loops $loops = NULL) {
        parent::__construct($severity, FALSE, $loops);
        $this->color = ($color === NULL) ? (php_sapi_name() == "cli") : (bool)$color;
    }
    
    protected function __log(Message $message) {
        if($this->color) {
            switch($message->severity) {
                case Message::EMERG:   $color = "45"; break;
                case Message::ALERT:   $color = "41"; break;
                case Message::CRIT:    $color = "35"; break;
                case Message::ERR:     $color = "31"; break;
                case Message::WARNING: $color = "33"; break;
                case Message::NOTICE:  $color = "32"; break;
                case Message::INFO:    $color = "36"; break;
                case Message::DEBUG:   $color = "34"; break;
            }
            $line = "\033[{$color}m[".$message->getSeverityName()."]\033[0m ".$message->message."\n";
        }
        else {
            $line = "[".$message->getSeverityName()."] ".$message->message."\n";
        }
        
        return fwrite(STDERR, $line) == strlen($line);
    }
}