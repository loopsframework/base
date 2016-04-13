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

use ArrayAccess;
use Loops;
use Loops\ArrayObject;
use Loops\Exception;
use Loops\Misc;
use Loops\Service;
use Loops\Session\SessionInterface;
use ReflectionClass;

abstract class PluginService extends Service {
    protected static $interface;
    protected static $default_plugin;
    
    public function __construct($classname, $interface, $default, Loops $loops = NULL) {
        parent::__construct($loops);
        $this->classname = $classname;
        $this->interface = $interface;
        $this->default   = $default;
    }
    
    protected static function getServiceClass() {
        return static::$serviceclass;
    }
    
    protected static function getInterface() {
        return static::$interface;
    }
    
    protected static function getClassname() {
        return static::$classname;
    }
    
    protected static function getDefaultConfig(Loops $loops = NULL) {
        $config = parent::getDefaultConfig($loops);
        $config->plugin = static::getDefaultPlugin($loops);
        return $config;
    }
    
    protected static function getDefaultPlugin(Loops $loops = NULL) {
        if(!static::$default_plugin) {
            throw new Exception("Service '".get_called_class()."' must define a default plugin.");
        }
        
        return static::$default_plugin;
    }
    
    public static function getService(ArrayObject $config, Loops $loops) {
        $config = static::getConfig($loops, $config);
        
        $classname = str_replace("%", $config->plugin, static::getClassname($loops));
        
        if(!class_exists($classname)) {
            throw new Exception("Class '$classname' is requested by '".get_called_class()."' but it was not found.");
        }
        
        $reflection = new ReflectionClass($classname);
        
        $interface = static::getInterface($loops);
        
        if(!$reflection->implementsInterface($interface)) {
            throw new Exception("Class '$classname' must implement '$interface'.");
        }

        return Misc::reflectionInstance($classname, $config);
    }
}