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

use ArrayIterator;
use IteratorAggregate;
use Doctrine\Common\Cache\ChainCache;
use Doctrine\Common\Cache\ArrayCache;
use Loops\Annotations\Last;
use Loops\Service;
use ReflectionClass;

class __Annotations implements IteratorAggregate {
    private $annotations;
    
    public function __construct($annotations) {
        $this->annotations = $annotations;
    }
    
    public function getIterator() {
        return new ArrayIterator($this->annotations);
    }
    
    public function find($name, $prefix = "Loops\Annotations\\") {
        return array_filter($this->annotations, function($annotation) use ($name, $prefix) {
            return is_a($annotation, $prefix.$name);
        });
    }
    
    public function findFirst($name, $prefix = "Loops\Annotations\\") {
        foreach($this->annotations as $annotation) {
            if(is_a($annotation, $prefix.$name)) {
                return $annotation;
            }
        }
        
        return NULL;
    }
    
    public function findLast($name, $prefix = "Loops\Annotations\\") {
        foreach(array_reverse($this->annotations) as $annotation) {
            if(is_a($annotation, $prefix.$name)) {
                return $annotation;
            }
        }
        
        return NULL;
    }
}

class __Annotations_Set implements IteratorAggregate {
    private $annotations = [];
    
    public function __construct(ReflectionClass $class, $reflection_method, $doctrine_method, $annotation_reader, $all) {
        $skip      = [];
        $result    = [];
        
        do {
            foreach($class->$reflection_method() as $reflection) {
                $name  = $reflection->getName();
                
                if($reflection->getDeclaringClass()->getName() != $class->getName()) {
                    continue;
                }

                if(!array_key_exists($name, $result)) {
                    $result[$name] = [];
                }
                
                foreach($annotation_reader->$doctrine_method($reflection) as $annotation) {
                    if($all && $annotation instanceof Last) {
                        $skip[] = $name;
                    }
                    
                    if(!in_array($name, $skip)) {
                        $result[$name][] = $annotation;
                    }
                }
            }
        } while($all && ($class = $class->getParentClass()));
        
        foreach($result as $name => $annotations) {
            $this->annotations[$name] = new __Annotations($annotations);
        }
    }
    
    public function __get($key) {
        return array_key_exists($key, $this->annotations) ? $this->annotations[$key] : NULL;
    }
    
    public function __isset($key) {
        return array_key_exists($key, $this->annotations);
    }
    
    public function getIterator() {
        return new ArrayIterator($this->annotations);
    }
    
    public function __call($name, $arguments) {
        $result = [];
        
        foreach($this->annotations as $key => $annotations) {
            $result[$key] = call_user_func_array([$annotations, $name], $arguments);
        }
        
        return array_filter($result);
    }
}

class __Class_Annotations extends __Annotations {
    public $properties;
    public $methods;
    
    public function __construct($classname, $annotation_reader, $all) {
        $class = new ReflectionClass($classname);
        
        $this->properties = new __Annotations_Set($class, "getProperties", "getPropertyAnnotations", $annotation_reader, $all);
        $this->methods = new __Annotations_Set($class, "getMethods", "getMethodAnnotations", $annotation_reader, $all);
        
        $annotations = [];
        
        do {
            foreach(array_merge([$class], $class->getTraits()) as $reflection) {
                foreach($annotation_reader->getClassAnnotations($reflection) as $annotation) {
                    if($all && $annotation instanceof Last) {
                        break 3;
                    }
                    
                    $annotations[] = $annotation;
                }
            }
        } while($all && ($class = $class->getParentClass()));
        
        parent::__construct($annotations);
    }
}

class Annotations extends Service {
    public function __get($key) {
        if(substr($key, 0, 1) == "_") {
            return $this->getStrict(str_replace("_", "\\", substr($key, 1)));
        }
        else {
            return $this->get(str_replace("_", "\\", $key));
        }
    }
    
    private function cachedGet($classname, $all) {
        $loops = $this->getLoops();

        if(is_object($classname)) {
            $classname = get_class($classname);
        }
        
        $key = "Loops-Service-Annotations-$classname-".($all ? "all" : "strict");
        
        static $cache;
        
        if(!$cache) {
            $cache = new ChainCache([new ArrayCache, $loops->getService("cache")]);
        }
        
        if($cache->contains($key)) {
            return $cache->fetch($key);
        }
        
        $result = new __Class_Annotations($classname, $this->getLoops()->getService("doctrine_annotation_reader"), $all);
        
        $cache->save($key, $result);
        
        return $result;
    }
    
    public function get($classname) {
        return $this->cachedGet($classname, TRUE);
    }
    
    public function getStrict($classname) {
        return $this->cachedGet($classname, FALSE);
    }
}