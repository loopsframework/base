<?php
/**
 * This file is part of the loops framework.
 *
 * @author Lukas <lukas@m-t.com>
 * @license https://raw.githubusercontent.com/loopsframework/base/master/LICENSE
 * @link https://github.com/loopsframework/base
 */


namespace Loops\Session;

class TestSession extends Session {
    public static $started = FALSE;
    public static $values = [];

    public static function reset() {
        static::$started = FALSE;
        static::$values = [];
    }

    public function start($lifetime = 0) {
        static::$started = TRUE;
    }

    public function isStarted() {
        return static::$started;
    }

    public function get($key) {
        return array_key_exists($key, static::$values) ? static::$values[$key] : NULL;
    }

    public function has($key) {
        return array_key_exists($key, static::$values);
    }

    public function delete($key) {
        unset(static::$values[$key]);
    }

    public function clear() {
        static::$values = [];
    }

    public function set($key, $value) {
        static::$values[$key] = $value;
    }

    public function destroy() {
    }
}
