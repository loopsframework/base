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
use Loops\Service;

abstract class AliasedService extends Service {
    protected static $aliases = [];

    protected static function getAliases() {
        return (array)static::$aliases;
    }

    public static function getService(ArrayObject $config, Loops $loops) {
        foreach(static::getAliases() as $alias) {
            if($loops->hasService($alias)) {
                return $loops->getService($alias);
            }
        }

        throw new Exception('No alias service could be found for '.get_class($this).'.');
    }

    public static function hasService(Loops $loops) {
        foreach(static::getAliases() as $alias) {
            if($loops->hasService($alias)) {
                return TRUE;
            }
        }

        return FALSE;
    }
}
