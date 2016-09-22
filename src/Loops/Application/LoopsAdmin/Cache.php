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

namespace Loops\Application\LoopsAdmin;

use Loops\Object;
use Loops\Annotations\Admin\Action;
use Loops\Annotations\Admin\Help;

/**
 * Defines actions for the LoopsAdmin application related to cache.
 *
 * @Help("This module contains actions for controlling the Loops cache service.")
 */
class Cache extends Object {
    /**
     * Calls flushAll on cache
     *
     * @Action("Clears the Loops cache via the flushAll call.")
     */
    public function clear() {
        $loops = $this->getLoops();

        if($loops->getService("cache")->flushAll()) {
            $loops->getService("logger")->info("Clearing cache was successful.");
            return 0;
        }
        else {
            $loops->getService("logger")->err("Clearing cache failed.");
            return 1;
        }
    }
}
