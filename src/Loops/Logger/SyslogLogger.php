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
use SplFileObject;
use Loops\Messages\Message;

class SyslogLogger extends Logger {
    protected function logMessage(Message $message) {
        return syslog($message->severity, $message->message);
    }
}
