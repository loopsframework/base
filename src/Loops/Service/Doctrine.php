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

use Doctrine\Common\Annotations\SimpleAnnotationReader;
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;
use Loops;
use Loops\Annotations\Access\ReadOnly;
use Loops\Misc;
use Loops\Service;

class Doctrine extends Service {
    /**
     * @ReadOnly
     */
    protected $driver;
    
    /**
     * @ReadOnly
     */
    protected $host;
    
    /**
     * @ReadOnly
     */
    protected $port;
    
    /**
     * @ReadOnly
     */
    protected $user;
    
    /**
     * @ReadOnly
     */
    protected $password;
    
    /**
     * @ReadOnly
     */
    protected $dbname;
    
    /**
     * @ReadOnly
     */
    protected $path;
    
    /**
     * @ReadOnly
     */
    protected $devmode;
    
    /**
     * @ReadOnly
     */
    protected $entity_prefix    = "Entities\\";
    
    /**
     * @ReadOnly
     */
    protected $entity_manager;
    
    public function __construct($driver = NULL, $dbname = NULL, $host = NULL, $port = NULL, $user = NULL, $password = NULL, $path = NULL, $devmode = TRUE, $entity_prefix = "Entities\\", Loops $loops = NULL) {
        parent::__construct($loops);
        
        $loops          = $this->getLoops();
        $application    = $loops->hasService('application') ? $loops->getService('application') : NULL;
        
        //check if mysql is linked via docker - configure default options if yes
        if(getenv('MYSQL_PORT') && preg_match('/^(.*?):\/\/(.*?):(.*?)$/', getenv('MYSQL_PORT'), $match)) {
            if($driver   === NULL) $driver   = 'pdo_mysql';
            if($host     === NULL) $host     = $match[2];
            if($port     === NULL) $port     = $match[3];
            if($user     === NULL) $user     = getenv('MYSQL_ENV_MYSQL_USER') ?: 'root';
            if($password === NULL) $password = getenv('MYSQL_ENV_MYSQL_PASSWORD') ?: getenv('MYSQL_ENV_MYSQL_ROOT_PASSWORD');
            if($dbname   === NULL) $dbname   = getenv('LOOPS_DATABASE') ?: (getenv('MYSQL_ENV_MYSQL_DATABASE') ?: 'loops');
        }
        
        //check if postgres is linked via docker - configure default options if yes
        if(getenv('POSTGRES_PORT') && preg_match('/^(.*?):\/\/(.*?):(.*?)$/', getenv('POSTGRES_PORT'), $match)) {
            if($driver   === NULL) $driver   = 'pdo_pgsql';
            if($host     === NULL) $host     = $match[2];
            if($port     === NULL) $port     = $match[3];
            if($user     === NULL) $user     = getenv('POSTGRES_ENV_POSTGRES_USER') ?: 'postgres';
            if($password === NULL) $password = getenv('POSTGRES_ENV_POSTGRES_PASSWORD');
            if($dbname   === NULL) $dbname   = getenv('LOOPS_DATABASE') ?: 'loops';
        }
        
        //default options - sqlite with database in cache directory
        if($driver === NULL) $driver = "pdo_sqlite";
        if($host   === NULL) $host   = "localhost";
        if($dbname === NULL) $dbname = "loops";
        if($path   === NULL) $path   = $application ? "{$application->cache_dir}/$dbname.sqlite": "$dbname.sqlite";
        
        //set vars as read-only for reference
        $this->driver        = $driver;
        $this->host          = $host;
        $this->port          = $port;
        $this->user          = $user;
        $this->password      = $password;
        $this->dbname        = $dbname;
        $this->path          = $path;
        $this->devmode       = $devmode;
        $this->entity_prefix = $entity_prefix;
        
        //setup doctrine entity maneger
        $databaseConfig = [ "driver"    => $driver,
                            "host"      => $host,
                            "user"      => $user,
                            "password"  => $password,
                            "dbname"    => $dbname,
                            "path"      => $path ];
        
        $doctrineConfig = Setup::createAnnotationMetadataConfiguration( [ "{$application->app_dir}/inc/".str_replace("\\", "/", $entity_prefix) ], // $paths
                                                                        $devmode,
                                                                        "{$application->cache_dir}/doctrine_proxies",
                                                                        $loops->hasService("cache") ? $loops->getService("cache") : NULL,
                                                                        $loops->getService("doctrine_annotation_reader") instanceof SimpleAnnotationReader );
        
        //autoloading of proxy classes - needed when unserializing doctrine entities
        //(doctrine should take care about this but is doesn't)
        spl_autoload_register(function($classname) use ($application) {
            //filename according to doctrine logic
            $file = str_replace("\\", "", substr($classname, strlen("DoctrineProxies\\"))).'.php';
            if(file_exists("{$application->cache_dir}/doctrine_proxies/$file")) {
                include("{$application->cache_dir}/doctrine_proxies/$file");
            };
        });
        
        $this->entity_manager = EntityManager::create($databaseConfig, $doctrineConfig);
    }
    
    public function offsetGet($key) {
        if(parent::offsetExists($key)) {
            return parent::offsetGet($key);
        }

        return $this->repository(Misc::camelize($key));
    }
    
    public function __call($name, $arguments) {
        return call_user_func_array([$this->entity_manager, $name], $arguments);
    }
    
    public function metadata($entity) {
        return $this->entity_manager->getMetadataFactory()->getMetadataFor(is_string($entity) ? $this->entity_prefix.$entity : get_class($entity));
    }
    
    public function repository($entity) {
        return $this->entity_manager->getRepository(is_string($entity) ? $this->entity_prefix.$entity : get_class($entity));
    }
}