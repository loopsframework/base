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
use Loops\Session\Session as LoopsSession;
use Loops\Session\SessionInterface;

class Session extends PluginService {
    protected static $classname         = "Loops\Session\%Session";
    protected static $interface         = "Loops\Session\SessionInterface";
    protected static $default_plugin    = "PHP";

    public static function getService(ArrayObject $config, Loops $loops) {
        $plugin = parent::getService($config, $loops);
        $plugin->start(0);
        register_shutdown_function(function() use ($plugin) {
            if($plugin->isStarted()) {
                $plugin->destroy();
            }
        });
        return $plugin;
    }
}
