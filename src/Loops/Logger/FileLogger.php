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

use SplFileObject;

use Loops;
use Loops\Misc;
use Loops\Exception;
use Loops\Messages\Message;

class FileLogger extends Logger {
    protected $fo;

    public function __construct($filename = "loops.log", $mode = "a+", $level = "notice", Loops $loops = NULL) {
        parent::__construct($level, $loops);

        $application = $this->getLoops()->getService("application");

        $filename = Misc::fullPath($filename, $application->cache_dir);

        $this->fo = new SplFileObject($filename, $mode);
    }

    protected function logMessage(Message $message) {
        $this->fo->fwrite("$message\n");
    }
}
