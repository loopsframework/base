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

use ArrayAccess;
use Loops;

interface SessionInterface {
    public function start($lifetime = 0);
    public function isStarted();
    public function get($key);
    public function has($key);
    public function set($key, $value);
    public function delete($key);
    public function clear();
    public function destroy();
}