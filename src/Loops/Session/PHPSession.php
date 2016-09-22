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

namespace Loops\Session;

class PHPSession extends Session {
    public function start($lifetime = 0) {
        if($this->isStarted()) {
            $this->destroy();
        }

        @session_start();
    }

    public function isStarted() {
        return (session_status() == PHP_SESSION_ACTIVE);
    }

    public function get($key) {
        return $this->has($key) ? unserialize($_SESSION[$key]) : NULL;
    }

    public function has($key) {
        return array_key_exists($key, $_SESSION);
    }

    public function delete($key) {
        unset($_SESSION[$key]);
    }

    public function clear() {
        $_SESSION = [];
    }

    public function set($key, $value) {
        $_SESSION[$key] = serialize($value);
    }

    public function destroy() {
        session_write_close();
    }
}
