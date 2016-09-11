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
    public $color;

    public function __construct($level = "info", $color = NULL, Loops $loops = NULL) {
        parent::__construct($level, $loops);
        //@todo Find out if there is a way to check if the terminal supports colors and adjust logic.
        $this->color = ($color === NULL) ? (php_sapi_name() == "cli") : (bool)$color;
    }

    protected function logMessage(Message $message) {
        $severity_name = $message->getSeverityName();

        $line = "$message\n";

        if($this->color) {
            switch($message->severity) {
                case Message::EMERGENCY: $color = "45"; break;
                case Message::ALERT:     $color = "41"; break;
                case Message::CRITICAL:  $color = "35"; break;
                case Message::ERROR:     $color = "31"; break;
                case Message::WARNING:   $color = "33"; break;
                case Message::NOTICE:    $color = "32"; break;
                case Message::INFO:      $color = "36"; break;
                case Message::DEBUG:     $color = "34"; break;
            }

            $line = preg_replace("/\[/", "\033[{$color}m[", $line, 1);
            $line = preg_replace("/\]/", "]\033[0m", $line, 1);
        }

        fwrite(STDERR, $line);
    }
}
