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
use Loops\Object;
use Psr\Log\LogLevel;

abstract class Logger extends Object implements LoggerInterface {
    use LoggerTrait;

    public function __construct($level = LogLevel::WARNING, Loops $loops = NULL) {
        parent::__construct($loops);

        $this->setLogLevel($level);
    }
}
