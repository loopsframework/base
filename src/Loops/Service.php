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

namespace Loops;

use Loops;
use Loops\Misc\AccessTrait;

/**
 * A class which aids in service creation by implementing useful default behaviour.
 *
 * This class extends from "Loops\Object" and implements the "Loops\ServiceInterface".
 * A Loops service is responsible for creating service instances that will be stored inside the Loops context.
 * On default the service class instantiate themselves but it is also possible to create different classes.
 *
 * The following can be configured in your child class if you inherit from this:
 *
 * 1. Shared service
 *
 * Set protected static property $shared to a boolean value (recommended) or override function isShared.
 * On default a service is shared.
 *
 * Example:
 * Define a service as non-shared.
 * <code>
 *     namespace Loops\Service;
 *     
 *     use Loops\Service;
 *
 *     class ExampleService2 extends Service {
 *         protected static isShared() {
 *             return FALSE;
 *         }
 *     }
 * </code>
 *
 * Example2:
 * <code>
 *     namespace Loops\Service;
 *     
 *     use Loops\Service;
 *     
 *     class ExampleService extends Service {
 *         protected static $shared = FALSE;
 *     }
 * </code>
 *
 * 2. Service default config
 *
 * A config array will be passed to the factory function. However, if your service extends from this class
 * you can also define default configuration values. The passed config array will be merged into this default
 * configuration. Values may be overwritten.
 * These configuration values are used to create the service instance (see "3. Service classname" for details).
 *
 * Default configuration values should be returned by function getDefaultConfig as an "Loops\ArrayObject".
 * On default the array defined at static property $default_config is converted into one.
 *
 * Example:
 * <code>
 *     namespace Loops\Service;
 *
 *     use Loops\Service;
 *     use Loops\ArrayObject;
 *
 *     class ExampleService extends Service {
 *         protected static function getDefaultConfig(Loops $loops = NULL) {
 *             $result = new ArrayObject;
 *             $result["foo"] = "bar";
 *             return $result;
 *         }
 *     }
 * </code>
 *
 * Example:
 * <code>
 *     namespace Loops\Service;
 *
 *     use Loops\Service;
 *
 *     class ExampleService2 extends Service {
 *         protected static $default_config = [ "foo" => "bar" ];
 *     }
 * </code>
 *
 * 3. Service classname
 *
 * On default, the factory method creates an instance of the service class (determined by get_called_class).
 * It is possible to manually set the class that should be created. For this, override function getClassname
 * or set the static property $classname.
 *
 * When the service class is instanciated, the values from the config file will be used as arguments for the
 * constructor. (see the documentation of Misc::reflectionInstance for details)
 *
 * Example:
 * The created service will be a "DateTime" object.
 * <code>
 *     namespace Loops\Service;
 *
 *     use Loops\Service;
 *
 *     class ExampleService extends Service {
 *         protected static function getClassname(Loops $loops = NULL) {
 *             return "DateTime";
 *         }
 *     }
 * </code>
 *
 * Example2:
 * Same, but shorter.
 * <code>
 *     namespace Loops\Service;
 *
 *     use Loops\Service;
 *
 *     class ExampleService2 extends Service {
 *         protected static $classname = "DateTime";
 *     }
 * </code>
 *
 * A drawback of using the static properties is the lack to dynamically create values (based on environment, configuration, etc).
 */
abstract class Service extends Object implements ServiceInterface {
    /**
     * @var bool Set to FALSE if the service should be created as non-shared
     */
    protected static $shared = TRUE;
    
    /**
     * @var array The default configuration for the service object. Values will be passed to the constructor based on keys and argument names.
     */
    protected static $default_config = [];
    
    /**
     * @var string|NULL The classname of the service. If set to NULL, an instance of the service class (determined by get_called_class) is used.
     */
    protected static $classname;
    
    /**
     * @var array<string> External classes that are required to create this service.
     */
    protected static $dependencies = [];
    
    /**
     * Returns if the service should be created as a shared service
     *
     * @param Loops The loops context
     * @return bool Returns if the service should be created as shared (by returning static property $shared).
     */
    public static function isShared(Loops $loops) {
        return static::$shared;
    }
    
    /**
     * Returns if external class dependencies are available
     *
     * Class dependencies should be defined in the static property $dependencies
     *
     * @param Loops $loops The loops context
     * @return array<string> Returns all needed dependencies (by returning static property $dependencies)
     */
    public static function getDependencies(Loops $loops) {
        return static::$dependencies;
    }

    /**
     * Returns a default configuration of the service.
     *
     * The static property $default_config is converted into a "Loops\ArrayObject" and returned.
     *
     * @param Loops The loops context
     * @return Loops\ArrayObject The default configuration that is used to create the service.
     */
    protected static function getDefaultConfig(Loops $loops = NULL) {
        return ArrayObject::fromArray(static::$default_config);
    }
    
    /**
     * Returns the classname of the service.
     *
     * The static property $classname is returned on default. If not set, the classname of this service class (determined)
     * by get_called_class) is returned.
     *
     * @param Loops The loops context
     * @return string The classname of the service class
     */
    protected static function getClassname(Loops $loops = NULL) {
        return static::$classname ?: get_called_class();
    }
    
    /**
     * Returns the complete config of a service
     *
     * A Loops context will be included in the configuration.
     * The config value will be generated by getting the default configuration of this class to which additional config values
     * can be merged.
     * If no additional values have been set, the config service of the Loops context will be looked for extra values. Here,
     * the value at the key that is determined as follows will be used:
     * 1. Get the classname of this class without the "Loops\Service\" part. e.g. "SmartyRenderer" for class "Loops\Service\SmartyRenderer"
     * 2. Underscore the classname (see Misc::undercore). e.g. "smarty_renderer" for classname "SmartyRenderer"
     * So in order to define extra configuration values for the service "Loops\Service\SmartyRenderer" these values must be accessable by
     * key "smarty_renderer" in the config service.
     * Example in case a config.ini file is used (which is true for most cases):
     * <code>
     *     ...
     *     
     *     [smarty_renderer]
     *     disable_security = TRUE
     *
     *     ...
     * </code>
     *
     * @param Loops $loops The Loops context. It will be included in the configuration. Defaults to the current Loops context if not set.
     * @param Loops\ArrayObject $config Additional config values that should be merged to the default config.
     * @return Loops\ArrayObject The configuration which combines default values with additional values and the loops context
     */
    public static function getConfig(Loops $loops = NULL, ArrayObject $config = NULL) {
        if(!$loops) {
            $loops = Loops::getCurrentLoops();
        }
        
        if(!$config) {
            $parts = explode("\\", get_called_class());
            if(count($parts) > 2 && array_slice($parts, 0, 2) == [ "Loops", "Service" ]) {
                $parts = array_slice($parts, 2);
            }

            $sectionname = Misc::underscore(implode("\\", $parts));
            
            $config = $loops->getService('config');
            $config = $config->offsetExists($sectionname) ? $config->offsetGet($sectionname) : new ArrayObject;
        }
        
        $result = static::getDefaultConfig($loops);
        $result->merge($config);
        $result->offsetSet('loops', $loops);
        return $result;
    }
    
    /**
     * The factory method that creates the service instance
     *
     * The service is instantiated by Misc::reflectionInstance.
     * The classname that is returned by function getClassname will be instanciated and arguments for the constructor are retrieved
     * by function getConfig.
     *
     * Without further changes in the child class, an instance of the service class will be instantiated and returned.
     *
     * @param Loops\ArrayObject $config The additiona config that will be merged into the default config.
     * @param Loops $loops The loops context
     * @return object The service object
     */
    public static function getService(ArrayObject $config, Loops $loops) {
        return Misc::reflectionInstance(static::getClassname($loops), static::getConfig($loops, $config));
    }
    
    /**
     * Checks if all dependent classnames are defined
     *
     * @param Loops\ArrayObject $config The additiona config that will be merged into the default config.
     * @param Loops $loops The loops context
     * @return bool TRUE if all dependencies are defined
     */
    public static function hasService(Loops $loops) {
        foreach(static::getDependencies($loops) as $classname) {
            if(!class_exists($classname)) {
                return FALSE;
            }
        }

        return TRUE;
    }
}