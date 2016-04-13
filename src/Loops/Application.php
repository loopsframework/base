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

use ArrayAccess;
use ReflectionClass;
use Doctrine\Common\Cache\ChainCache;
use Doctrine\Common\Cache\ArrayCache;
use Loops;
use Loops\ArrayObject;
use Loops\Exception;
use Loops\Http\Request;
use Loops\Http\Response;
use Loops\Annotations\Access\ReadOnly;

/**
 * The Loops Application
 *
 * Base class for loops applications.
 * An application is defined by the files (classes, templates, etc) in a folder that is called the application directory.
 * This folders contents are typically read-only and are not a subject to change. It defines the applications behaviour.
 *
 * There may be different type of Loops applications. For example Web applications that process RESTful requests or command line applications.
 * The application logic should be executed by calling the run method which must be implemented by child classes.
 *
 * When a Loops application is created it will create and/or setup a Loops object.
 * See the documentation of the constructor for details.
 */
abstract class Application extends Object {
    /**
     * @var string The path to the application directory
     * @ReadOnly
     */
    protected $app_dir;
    
    /**
     * @var string The path to the cache directory
     * @ReadOnly
     */
    protected $cache_dir;
    
    /**
     * @var array An array with registered autoload directories (PSR-4)
     * @ReadOnly
     */
    protected $include_dir;
    
    /**
     * @var bool Specifies if the cached autoload feature is enabled
     * @ReadOnly
     */
    protected $enable_cached_autoload;
    
    /**
     * @var bool Specifies if the cached should be flushed on changes in app_dir
     *
     * This check will only be made in debug mode
     * 
     * @ReadOnly
     */
    protected $enable_cache_flush;
    
    /**
     * @var string Location of the boot file
     * @ReadOnly
     */
    protected $boot;
    
    /**
     * Setups a Loops application.
     *
     * This constructor will take a Loops context (via the $config parameter) or create a new one.
     *
     * The application will register itself as a service named 'application'.
     * Therefore, the application instance can be quickly accessed in a Loops\Object by $this->application.
     *
     * In Debug mode, changes in the application folder are attempted to be detected and if so, the complete cache (according to service 'cache') and renderer cache files (according to service 'renderer') will be deleted.
     *
     * The application will also speed up autoloading classes by remembering the filenames of class definitions in the cache module.
     * This behaviour can be disabled by setting the config value ->application->enable_cached_autoload to FALSE
     *
     * Include Dir:
     * The value ->application->include_dir is read from the config and defines paths that are consided for autoloading in the PSR-4 format.
     * This value can be an absolute path or relative to $app_dir. If not specified, [ 'inc' ] is used by default which specifies the 'inc' directory insied the application directory.
     *
     * Boot:
     * The value ->application->boot can hold a name of a php file that is executed after application creation.
     * This value can be an absolute path or relative to $app_dir. By default the file 'boot.php' inside the application directory is used.
     * Inside the boot script only the variable $loops is set.
     * 
     * @param string $app_dir The application directory
     * @param string $cache_dir The directory for temporary files (as full paths or relative to $appdir)
     * @param string|Loops|Loops\ArrayObject $config A Loops context, a Loops\ArrayObject that is used to create a Loops context or the location of a php file that returns a Loops\ArrayObject or Loops context.
     * @param bool $boot Specifies if the boot script should be executed
     */
    public function __construct($app_dir, $cache_dir = "/tmp", $config = "config.php", $boot = TRUE) {
        //setup directories
        $app_dir   = realpath($app_dir);
        $cache_dir = Misc::fullPath($cache_dir, $app_dir);

        //make a context from config file
        if(is_string($config)) {
            $config = include(Misc::fullPath($config, $app_dir));
        }
        
        if($config instanceof ArrayObject) {
            $loops = new Loops($config, @$config->loops->debug === NULL ? TRUE : (bool)@$config->loops->debug);
        }
        elseif($config instanceof Loops) {
            $loops = $config;
        }
        else {
            throw new Exception("Failed to create Loops Context.");
        }
        
        //register application service
        $loops->registerService("application", $this);

        $this->app_dir                  = $app_dir;
        $this->cache_dir                = $cache_dir;
        $this->include_dir              = (array)(@$loops->config->application->include_dir === NULL ?  [ "inc" ] : $loops->config->application->include_dir);
        $this->boot                     = @$loops->config->application->boot ?: "boot.php";
        $this->enable_cached_autoload   = @$loops->config->application->enable_cached_autoload === NULL ? TRUE : $loops->config->application->enable_cached_autoload;
        $this->enable_cache_flush       = @$loops->config->application->enable_cache_flush === NULL ? TRUE : $loops->config->application->enable_cache_flush;

        if(substr($this->boot, 0, 1) != "/") {
            $this->boot = "$app_dir/".$this->boot;
        }
        
        foreach($this->include_dir as $key => $include_dir) {
            if(substr($include_dir, 0, 1) != "/") {
                $this->include_dir[$key] = "$app_dir/$include_dir";
            }
        }
        
        //register autoload
        foreach($this->include_dir as $path) {
            spl_autoload_register(function($classname) use ($path) {
                $filename = $path."/".str_replace("\\", "/", $classname).".php";
                if(!file_exists($filename)) return;
                require_once($filename);
            });
        }
        
        if($this->enable_cached_autoload) {
            $this->setupCachedAutoload($loops);
        }

        //delete cache if files have changed - do this check only in debugmode
        if($this->enable_cache_flush && $loops->debug) {
            $cache    = $loops->getService("cache");
            $renderer = $loops->getService("renderer");
            
            $dirs = [];
            
            if($this->enable_cache_flush & 0x1) {
                $dirs[] = $this->app_dir;
            }
            
            if($this->enable_cache_flush & 0x2) {
                $dirs = array_merge($dirs, $this->include_dir);
            }
            
            if($this->enable_cache_flush & 0x4) {
                $dirs = array_merge($dirs, $renderer->view_dir);
            }
            
            if($file = Misc::lastChange($dirs, $cache, $key)) {
                Misc::recursiveUnlink("{$this->cache_dir}/renderer_cache");
                $cache->flushAll();
                $cache->save($key, $file->getMTime());
            }
        }
        
        parent::__construct($loops);
        
        //boot if requested
        if($boot) {
            $this->boot();
        }
    }
    
    public function getAppDir() {
        return $this->app_dir;
    }
    
    public function getCacheDir() {
        return $this->cache_dir;
    }
    
    protected function boot() {
        self::isolated_boot($this->getLoops());
    }
    
    /**
     * Initialize autoloading
     */
    private function setupCachedAutoload($loops) {
        $cache = $loops->getService("cache");

        $check = [];
        
        register_shutdown_function(function() use ($cache, &$check) {
            foreach($check as $classname) {
                if(class_exists($classname, FALSE) || interface_exists($classname, FALSE) || trait_exists($classname, FALSE)) {
                    $reflection = new ReflectionClass($classname);
                    if($filename = $reflection->getFileName()) {
                        $key = "Loops-Application-autoload-$classname";
                        $cache->save($key, $filename);
                    }
                }
            }
        });
        
        spl_autoload_register(function($classname) use ($cache, &$check) {
            $key = "Loops-Application-autoload-$classname";
            if($cache->contains($key)) {
                $filename = $cache->fetch($key);
                require_once($filename);
            }
            else {
                $check[] = $classname;
            }
        }, FALSE, TRUE);
    }
    
    /**
     * Get a list of all classnames that are defined in the app directory
     *
     * @return array<string> The list of all classes.
     */
    public function definedClasses() {
        $loops = $this->getLoops();
        
        $cache = $loops->getService("cache");
        
        $key = "Loops-Application-definedClasses";
        
        if($cache->contains($key)) {
            return $cache->fetch($key);
        }

        $require = function($dir) use (&$require, &$classes) {
            foreach(scandir($dir) as $file) {
                if(substr($file, 0, 1) == '.') {
                    continue;
                }
                
                $filename = "$dir/$file";
                
                if(is_dir($filename)) {
                    $require($filename);
                }
                else {
                    require_once($filename);
                }
            }
        };
        
        $dirs = $this->include_dir;
        
        array_walk($dirs, $require);
        
        $classes = array_values(array_filter(get_declared_classes(), function($classname) use ($dirs, $cache) {
            $reflection = new ReflectionClass($classname);
            $filename = $reflection->getFileName();
            
            if(!$filename) {
                return FALSE;
            }
            
            if($this->enable_cached_autoload) {
                $key = "Loops-Application-autoload-$classname";
                $cache->save($key, $filename);
            }

            foreach($dirs as $dir) {
                if(substr($filename, 0, strlen($dir)) == $dir) {
                    return TRUE;
                }
            }
            
            return FALSE;
        }));
        
        $cache->save($key, $classes);
        
        return $classes;
    }
    
    /**
     * Runs the application
     *
     * This method should implement the application processing logic.
     */
    abstract public function run();
    
    
    /**
     * Includes the boot file in an isolated namespace
     */
    private static function isolated_boot($loops) {
        if(is_file($loops->getService("application")->boot) && is_readable($loops->getService("application")->boot)) {
            require($loops->getService("application")->boot);
        }
    }
}