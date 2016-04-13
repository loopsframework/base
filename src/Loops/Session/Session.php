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

abstract class Session implements SessionInterface {
    public function __get($key) {
        return $this->get($key);
    }
    
    public function __isset($key) {
        return $this->has($key);
    }

    public function __unset($key) {
        $this->delete($key);
    }
    
    public function __set($key, $value) {
        return $this->set($key, $value);
    }
}