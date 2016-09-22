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

namespace Loops\Application;

use SplFileObject;
use Loops\Exception;
use Loops\Application;
use Loops\Jobs\Scheduler;
use Loops\Annotations\Access\ReadOnly;
use donatj\Flags;

/**
 * @todo
 */
abstract class CliApplication extends Application {
    /**
     * @ReadOnly
     */
    protected $command;

    /**
     * @ReadOnly
     */
    protected $arguments;

    /**
     * @ReadOnly
     */
    protected $flags;

    /**
     * @ReadOnly
     */
    protected $stdout;

    /**
     * @ReadOnly
     */
    protected $stderr;

    /**
     * @ReadOnly
     */
    protected $stdin;

    public function __construct($app_dir, array $arguments, $stdout = NULL, $stderr = NULL, $stdin = NULL, $cache_dir = "/tmp", $config = "config.php") {
        $this->flags = new Flags;
        $this->flags->string("app-dir", $app_dir, "Specify the Loops application directory. [Default: $app_dir]");
        $this->flags->string("cache-dir", $cache_dir, "Specify the Loops cache directory. [Default: $cache_dir]");
        $this->flags->string("config", $config, "Location of the Loops configuration. [Default: $config]");

        $this->command = $arguments[0];
        $this->arguments = array_splice($arguments, 1);
        $this->stdout = $stdout ?: STDOUT;
        $this->stderr = $stderr ?: STDERR;
        $this->stdin  = $stdin ?: STDIN;

        //parse (do not use overloaded method)
        $options = self::parse(FALSE);

        parent::__construct($options["app-dir"], $options["cache-dir"], $options["config"]);
    }

    protected function parse($strict = TRUE) {
        try {
            $this->flags->parse($this->arguments, !$strict, FALSE);
        }
        catch(Exception $e) {
            return $this->printError($e->getMessage());
        }

        return array_merge($this->flags->shorts(), $this->flags->longs());
    }

    protected function printError($error, $errno = 1) {
        $this->fwrite($this->stderr, "Error: $error\n");
        $this->fwrite($this->stderr, "\n");
        return $errno;
    }

    protected function printHelp($banner = "") {
        if($banner) {
            $this->fwrite($this->stdout, "$banner\n");
            $this->fwrite($this->stdout, "\n");
        }

        $this->fwrite($this->stdout, $this->flags->getDefaults());
        return 0;
    }

    public function run() {
        return $this->exec($this->arguments);
    }

    public function stdout($string) {
        $this->fwrite($this->stdout, $string);
    }

    public function stderr($string) {
        $this->fwrite($this->stderr, $string);
    }

    private function fwrite($pointer, $string) {
        if($pointer instanceof SplFileObject) {
            return $pointer->fwrite($string);
        }

        if(is_resource($pointer)) {
            return fwrite($pointer, $string);
        }

        throw new Exception("Bad filepointer.");
    }

    abstract function exec($params);
}
