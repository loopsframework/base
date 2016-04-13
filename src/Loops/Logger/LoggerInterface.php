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

interface LoggerInterface {
    public function log(Message $message);
    public function setSeverity($severity);
    public function getSeverity();
}