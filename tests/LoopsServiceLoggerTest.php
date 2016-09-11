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

require_once(__DIR__."/LoopsTestCase.php");

use Loops\Service\Logger;
use Loops\Logger\Logger as LoopsLogger;
use Loops\Logger\LoggerInterface;
use Loops\Messages\Message;
use Loops\Logger\LoggerTrait;
use Psr\Log\LogLevel;

class TestLogger extends LoopsLogger implements LoggerInterface {
    public $messages = [];

    public function logMessage(Message $message) {
        $this->messages[] = $message;
    }
}

class LoopsServiceLoggerTest extends LoopsTestCase {
    public function setUp() {
        parent::setUp();

        $this->test_logger = new TestLogger;
        $this->logger = new Logger;
        $this->logger->attach($this->test_logger);

        //php_unit sets error handler by itself and overwrites our error_handler
        set_error_handler([$this->logger, "php_error_handler"], E_ALL);
    }

    public function tearDown() {
        restore_error_handler();

        unset($this->logger);
        unset($this->test_logger);

        parent::tearDown();
    }

    /**
     * Override log level
     */
    public function testConstructorLogLevel() {
        $logger = new Logger(LogLevel::ERROR);
        $logger->attach($this->test_logger);

        $this->assertCount(0, $this->test_logger->messages);

        $logger->info("test1");

        $this->assertCount(0, $this->test_logger->messages);

        $logger->error("test2");

        $this->assertCount(1, $this->test_logger->messages);

        $logger->emergency("test3");

        $this->assertCount(2, $this->test_logger->messages);

        $logger->debug("test4");

        $this->assertCount(2, $this->test_logger->messages);
    }

    /**
     * PHP error logging is forwared
     */
    public function testConstructorPhpLogging() {
        user_error("test", E_USER_WARNING);

        $this->assertEquals("E_USER_WARNING: test", end($this->test_logger->messages)->message);
    }

    /**
     * Custom PHP error logging constants can be used
     */
    public function testConstructorCustomPhpLogging() {
        $logger = new Logger(LogLevel::WARNING, E_USER_WARNING | E_USER_DEPRECATED);
        $test_logger = new TestLogger;
        $logger->attach($test_logger);

        user_error("test1", E_USER_DEPRECATED);

        $this->assertEquals("E_USER_DEPRECATED: test1", end($test_logger->messages)->message);

        user_error("test2", E_USER_NOTICE);

        $this->assertEquals("E_USER_DEPRECATED: test1", end($test_logger->messages)->message);

        user_error("test3", E_USER_WARNING);

        $this->assertEquals("E_USER_WARNING: test3", end($test_logger->messages)->message);
    }

    public function testConstructorWithLine() {
        $logger = new Logger(LogLevel::WARNING, E_ALL, TRUE);
        $test_logger = new TestLogger;
        $logger->attach($test_logger);

        user_error("test", E_USER_WARNING);

        $this->assertEquals("E_USER_WARNING: test in ".__FILE__." on line: ".(__LINE__ - 2), end($test_logger->messages)->message);
    }

    public function testConstructorWithTrace() {
        // @todo Not implemented yet
    }

    /**
     * This will indirectly test getService
     */
    public function testGetService() {
        $this->assertInstanceOf("Loops\Service\Logger", $this->app->logger);
    }

    public function testErrnoToString() {
        $this->assertEquals("E_ERROR", Logger::errnoToString(E_ERROR));
        $this->assertEquals("E_WARNING", Logger::errnoToString(E_WARNING));
        $this->assertEquals("E_PARSE", Logger::errnoToString(E_PARSE));
        $this->assertEquals("E_NOTICE", Logger::errnoToString(E_NOTICE));
        $this->assertEquals("E_CORE_ERROR", Logger::errnoToString(E_CORE_ERROR));
        $this->assertEquals("E_CORE_WARNING", Logger::errnoToString(E_CORE_WARNING));
        $this->assertEquals("E_COMPILE_ERROR", Logger::errnoToString(E_COMPILE_ERROR));
        $this->assertEquals("E_COMPILE_WARNING", Logger::errnoToString(E_COMPILE_WARNING));
        $this->assertEquals("E_USER_ERROR", Logger::errnoToString(E_USER_ERROR));
        $this->assertEquals("E_USER_WARNING", Logger::errnoToString(E_USER_WARNING));
        $this->assertEquals("E_USER_NOTICE", Logger::errnoToString(E_USER_NOTICE));
        $this->assertEquals("E_STRICT", Logger::errnoToString(E_STRICT));
        $this->assertEquals("E_RECOVERABLE_ERROR", Logger::errnoToString(E_RECOVERABLE_ERROR));
        $this->assertEquals("E_DEPRECATED", Logger::errnoToString(E_DEPRECATED));
        $this->assertEquals("E_USER_DEPRECATED", Logger::errnoToString(E_USER_DEPRECATED));
        $this->assertEquals("", Logger::errnoToString(-1));
    }

    /**
     * Multiple loggers receive the message
     */
    public function testLogMessage() {
        $test_logger = new TestLogger;
        $this->logger->attach($test_logger);

        $this->logger->warning("test");

        $this->assertCount(1, $test_logger->messages);
        $this->assertCount(1, $this->test_logger->messages);
    }

    /**
     * Attached loggers receive logging messages
     */
    public function testAttach() {
        $test_logger = new TestLogger;
        $this->logger->attach($test_logger);

        $this->logger->warning("test");

        $this->assertCount(1, $test_logger->messages);
    }

    /**
     * After a logger is detached it doesn't receive any messages anymore
     */
    public function testDetach() {
        $test_logger = new TestLogger;
        $this->logger->attach($test_logger);

        $this->logger->warning("test1");

        $this->assertCount(1, $test_logger->messages);

        $this->logger->detach($test_logger);

        $this->logger->warning("test2");

        $this->assertCount(1, $test_logger->messages);
    }

    /**
     * Get will return boolean based on loglevel setting
     */
    public function testGet() {
        $logger = new Logger(LogLevel::NOTICE);
        $this->assertTrue($logger->emergency);
        $this->assertTrue($logger->alert);
        $this->assertTrue($logger->critical);
        $this->assertTrue($logger->error);
        $this->assertTrue($logger->warning);
        $this->assertTrue($logger->notice);
        $this->assertFalse($logger->info);
        $this->assertFalse($logger->debug);
        $this->assertNull($logger->other_value);
    }

    /**
     * Log level checks work as expected
     */
    public function testIsValidLogLevel() {
        $this->assertTrue(Logger::isValidLogLevel("emergency"));
        $this->assertTrue(Logger::isValidLogLevel("alert"));
        $this->assertTrue(Logger::isValidLogLevel("critical"));
        $this->assertTrue(Logger::isValidLogLevel("error"));
        $this->assertTrue(Logger::isValidLogLevel("warning"));
        $this->assertTrue(Logger::isValidLogLevel("notice"));
        $this->assertTrue(Logger::isValidLogLevel("info"));
        $this->assertTrue(Logger::isValidLogLevel("debug"));
        $this->assertFalse(Logger::isValidLogLevel("other_value"));
    }
}
