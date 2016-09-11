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

namespace Loops\Service;

use Loops;
use Loops\ArrayObject;
use Loops\Object;
use Loops\Service;
use Loops\Messages\Message;
use Loops\Logger\LoggerInterface;
use Loops\Logger\LoggerTrait;
use Loops\Misc;
use Psr\Log\LogLevel;

/**
 * The loops logger.
 *
 * Multiple LoggerInterfaces can be attached, if a class requests logging a message
 * through this class, it will be forwared to all attached LoggerInterfaces.
 *
 * The loops logger will also log php error messages.
 */
class Logger extends Service implements LoggerInterface {
    use LoggerTrait;

    /**
     * @var array<Loops\Logger\LoggerInterface> All attached loggers
     */
    protected $logger = [];

    private static $php_error_handler_set = FALSE;
    private static $error_handling_instances = [];

    /**
     * @param string $level Only log messages with this level or higher.
     * @param int $log_php_errors Set to a PHP error constant to enable loggin of php generated errors.
     * @param bool $with_line Set if the file and line should be displayed on PHP errors.
     * @param bool $with_trace Set if the context/trace infomation should be displayed on PHP errors.
     * @param Loops The loops object
     */
    public function __construct($level = LogLevel::WARNING, $log_php_errors = NULL, $with_line = FALSE, $with_trace = FALSE, Loops $loops = NULL) {
        if($log_php_errors === NULL) {
            $log_php_errors = error_reporting();
        }

        parent::__construct($loops);

        $this->setLogLevel($level);

        if(!self::$php_error_handler_set) {
            set_error_handler([__CLASS__, "php_error_handler"], E_ALL);
            self::$php_error_handler_set = TRUE;
        }

        self::$error_handling_instances[] = [ $this, $log_php_errors, $with_line, $with_trace ];
    }

    /**
     * Creates the logging service.
     *
     * A logger name can be specified in config variable 'plugin'.
     * An instance of "Loops\Logger\%Logger" will be attached to this logger
     * with % being the camelized version of the loggers name.
     * An array can also be passed to attach multiple loggers.
     * Alternatively multiple loggers can be passed as a comma separated
     * string.
     * Other configuration parameters are passed to the constructor(s) of the
     * logging class(es).
     */
    public static function getService(ArrayObject $config, Loops $loops) {
        $logger = parent::getService($config, $loops);

        $plugin = $config->offsetExists("plugin") ? $config->offsetGet("plugin") : "stderr";

        foreach(array_filter(is_array($plugin) ? $plugin : explode(",", $plugin)) as $plugin) {
            $classname = "Loops\Logger\\".Misc::camelize($plugin)."Logger";
            $logger->attach(Misc::reflectionInstance($classname, $config));
        }

        return $logger;
    }

    /**
     * Desctructor
     *
     * Removes itself from PHP logging
     */
    public function __destruct() {
        self::$error_handling_instances = array_filter(self::$error_handling_instances, function($set) {
            return $set[0] !== $this;
        });

        if(!self::$error_handling_instances) {
            restore_error_handler();
            self::$php_error_handler_set = FALSE;
        }
    }

    /**
     * Converts PHP errors to a Loops\Messages\Message and passes it to all
     * Loops\Service\Logger instances.
     */
    public static function php_error_handler($errno, $errstr, $errfile, $errline, $errcontext) {
        if($errno & error_reporting()) {
            foreach(self::$error_handling_instances as list($logger, $level, $with_line, $with_trace)) {
                if(!($errno & $level)) {
                    continue;
                }

                switch($errno) {
                    case E_PARSE:
                        $severity = Message::ALERT;
                        break;
                    case E_CORE_ERROR:
                    case E_COMPILE_ERROR:
                        $severity = Message::CRITICAL;
                        break;
                    case E_ERROR:
                    case E_USER_ERROR:
                    case E_RECOVERABLE_ERROR:
                        $severity = Message::ERROR;
                        break;
                    case E_WARNING:
                    case E_CORE_WARNING:
                    case E_COMPILE_WARNING:
                    case E_USER_WARNING:
                        $severity = Message::WARNING;
                        break;
                    case E_NOTICE:
                    case E_USER_NOTICE:
                        $severity = Message::NOTICE;
                        break;
                    case E_DEPRECATED:
                    case E_USER_DEPRECATED:
                        $severity = Message::INFO;
                        break;
                    case E_STRICT:
                    default:
                        $severity = Message::DEBUG;
                        break;
                }

                $message = self::errnoToString($errno).": $errstr";

                if($with_line) {
                    $message = "$message in $errfile on line: $errline";
                }

                if($with_trace) {
                    throw new Exception("Not implemented yet.");
                }

                $logger->logMessage(new Message($message, $severity));
            }
        }

        return TRUE;
    }

    /**
     * Returns the name of a PHP error constant
     * @param int The PHP error constant
     * @return string The name of the error constant
     */
    public static function errnoToString($type) {
        switch($type) {
            case E_ERROR: // 1 //
                return 'E_ERROR';
            case E_WARNING: // 2 //
                return 'E_WARNING';
            case E_PARSE: // 4 //
                return 'E_PARSE';
            case E_NOTICE: // 8 //
                return 'E_NOTICE';
            case E_CORE_ERROR: // 16 //
                return 'E_CORE_ERROR';
            case E_CORE_WARNING: // 32 //
                return 'E_CORE_WARNING';
            case E_COMPILE_ERROR: // 64 //
                return 'E_COMPILE_ERROR';
            case E_COMPILE_WARNING: // 128 //
                return 'E_COMPILE_WARNING';
            case E_USER_ERROR: // 256 //
                return 'E_USER_ERROR';
            case E_USER_WARNING: // 512 //
                return 'E_USER_WARNING';
            case E_USER_NOTICE: // 1024 //
                return 'E_USER_NOTICE';
            case E_STRICT: // 2048 //
                return 'E_STRICT';
            case E_RECOVERABLE_ERROR: // 4096 //
                return 'E_RECOVERABLE_ERROR';
            case E_DEPRECATED: // 8192 //
                return 'E_DEPRECATED';
            case E_USER_DEPRECATED: // 16384 //
                return 'E_USER_DEPRECATED';
        }
        return "";
    }

    /**
     * Forwards the log message to all attached logger interfaces
     *
     * @param Loops\Message\Message The log message
     */
    public function logMessage(Message $message) {
        foreach($this->logger as $logger) {
            $logger->logMessage($message);
        }
    }

    /**
     * Attaches a logger instance to this logger
     * @param Loops\Logger\LoggerInterface The logger instance
     */
    public function attach(LoggerInterface $logger) {
        $this->logger[] = $logger;
    }

    /**
     * Removes an attached logger instance
     *
     * If the same instance has been attached multiple times, only one instance
     * will actually be removed.
     *
     * @param Loops\Logger\LoggerInterface The logger instance that is going to be removed
     * @return bool TRUE if the interface has been removed or FALSE if it didn't exist.
     */
    public function detach(LoggerInterface $logger) {
        $key = array_search($logger, $this->logger, TRUE);

        if($key === FALSE) {
            return FALSE;
        }

        unset($this->logger[$key]);
        return TRUE;
    }

    /**
     * If a log level name is given TRUE or FALSE is returned based on if
     * logging of this level is enabled.
     *
     * If no valid log level is given, __get of the parent class is returned.
     *
     * @param string $key A LogLevel name.
     * @return mixed TRUE or FALSE if logging of the given log level is available
     */
    public function __get($key) {
        if(self::isValidLogLevel($key)) {
            return $this->isLogging($key);
        }

        return parent::__get($key);
    }

    /**
     * Checks if a given string is a valid LogLevel name
     *
     * @param string The log level
     * @return bool TRUE if the given name is a valid LogLevel name
     */
    public static function isValidLogLevel($level) {
        return @constant("Psr\Log\Loglevel::".strtoupper($level)) == $level;
    }
}
