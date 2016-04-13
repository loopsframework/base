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

use Loops\Messages\Message;

class ErrorlogLogger extends Logger {
    public function __log(Message $message) {
        return error_log($this->line($message));
    }
}