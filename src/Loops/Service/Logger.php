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

class Logger extends PluginService {
    protected static $classname         = 'Loops\Logger\%Logger';
    protected static $interface         = 'Loops\Logger\LoggerInterface';
    protected static $default_plugin    = 'File';
}