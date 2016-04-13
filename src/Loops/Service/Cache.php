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

use Doctrine\Common\Cache\FlushableCache;
use Loops;
use Loops\ArrayObject;
use Loops\Annotations\Access\ReadOnly;
use Loops\Misc;

class Cache extends PluginService {
    protected static $classname = "Doctrine\Common\Cache\%Cache";
    protected static $interface = "Doctrine\Common\Cache\Cache";
    
    protected static function getDefaultPlugin(Loops $loops) {
        if(function_exists('apc_add')) return 'Apc';
        if(function_exists('apcu_add')) return 'Apcu';
        if(function_exists('xcache_set')) return 'Xcache';
        if($loops->hasService('redis')) return 'Redis';
        return 'Filesystem';
    }
    
    protected static function getDefaultConfig(Loops $loops) {
        $config = parent::getDefaultConfig($loops);
        
        if($config->plugin == 'Filesystem') {
            $config->directory = $loops->hasService('application') ? $loops->getService('application')->getCacheDir().'/cache' : '/tmp';
        }
        
        if($config->plugin == 'Redis') {
            $config->database = 1;
        }

        return $config;
    }
    
    public static function getService(ArrayObject $config, Loops $loops) {
        $cache = parent::getService($config, $loops);
        
        $classname = get_class($cache);
        
        if($classname == 'Doctrine\Common\Cache\RedisCache') {
            $cache->setRedis($loops->createService('redis', static::getConfig($loops, $config)));
        }
        
        return $cache;
    }
}