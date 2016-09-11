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

use Loops\Service\Logger as LoggerService;
use Loops\Messages\Message;
use Psr\Log\LoggerTrait as PsrLoggerTrait;
use Psr\Log\LogLevel;
use InvalidArgumentException;

trait LoggerTrait {
    use PsrLoggerTrait;

    /**
     * The logger will only forward log messages that are equal or greater than
     * this log level setting.
     */
    protected $level = LogLevel::WARNING;
    protected $severity = Message::WARNING;

    public function log($level, $message, array $context = []) {
        if(!$this->isLogging($level)) {
            return;
        }

        $severity = self::logLevelToSeverity($level);

        $message = new Message((string)$message, $severity);

        $this->logMessage($message);
    }

    /**
     * Calling this function is allowed to bypass the LogLevel check
     * You should use the Psr\Log\LoggerInterface to send messages
     */
    abstract protected function logMessage(Message $message);

    public function setLogLevel($level) {
        $this->level = $level;
        $this->severity = self::logLevelToSeverity($this->level);
    }

    public function getLogLevel() {
        return $this->level;
    }

    public static function logLevelToSeverity($level) {
        if(!LoggerService::isValidLogLevel($level)) {
            throw new InvalidArgumentException("Invalid logging level: $level");
        }

        return constant("Loops\Messages\Message::".strtoupper($level));
    }

    public function isLogging($level) {
        $current_severity = self::logLevelToSeverity($level);
        return $current_severity <= $this->severity;
    }
}
