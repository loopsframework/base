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

namespace Loops\Misc;

use ReflectionClass;
use ReflectionProperty;
use ReflectionMethod;
use IteratorAggregate;

use Loops;
use Loops\Exception;
use Loops\Object;
use Loops\Annotations\Access\Expose;
use Loops\Annotations\Access\ReadOnly;
use Loops\Annotations\Access\ReadWrite;
use Loops\Annotations\Access\Sleep;

use Doctrine\Common\Cache\ChainCache;
use Doctrine\Common\Cache\ArrayCache;

/**
 * A trait that implements Loops access logic.
 *
 * With this trait it is possible to define variables as read-only, registering getter/setter and more.
 * 
 * The following features can be used on the object:
 *  1) Exposing properties (= set traversable properties)
 *  2) ReadOnly properties
 *  3) ReadWrite properties (getter/setter)
 *  4) Smart serializing
 *  5) Smart traversal
 *
 * Details:
 * 
 * 1) Exposing properties:
 * An exposed variable in loops is a variable that will be returned when traversing the object.
 * To use this feature, your class must additionally implement IteratorAggregate.
 * By default, only public variables are exposed. This is the same as PHPs default behaviour.
 * A (protected) property can be additionally exposed by adding the Loops\Annotations\Access\Expose annotation.
 *
 * Example:
 * <code>
 *     use Loops\Misc\AccessTrait;
 *     use Loops\Annotations\Access\Expose;
 *     
 *     class ExposeSample {
 *         use AccessTrait;
 *
 *         /**
 *          * \@Expose
 *          {@*}
 *         protected $foo = "bar";
 *     }
 *
 *     $object = new ExposeSample;
 *     foreach($object as $key => $value) {
 *         echo "$key$value"; //prints: foobar
 *     }
 * </code>
 *
 * 2) ReadOnly properties:
 * As the name suggests, a read-only property can be accessed but not written. This is made possible by PHPs magic __get functionality.
 * A (protected) property can be made read-only by adding the Loops\Annotations\Access\ReadOnly annotation.
 * If attemting to set a read-only property (outside of class context), an exception will be thrown.
 * It is also possible to set a getter for ReadOnly variables. In this case, on every attemt in reading the property, the getter method
 * will be executed and its result returned.
 *
 * Example:
 * <code>
 *     use Loops\Misc\AccessTrait;
 *     use Loops\Annotations\Access\ReadOnly;
 *     
 *     class ExposeSample2 {
 *         use AccessTrait;
 *
 *         /**
 *          * \@ReadOnly
 *          {@*}
 *         protected $ro = "foo";
 *
 *         /**
 *          * \@ReadOnly("getBar")
 *          {@*}
 *         protected $ro_by_getter;
 *
 *         public function getBar() {
 *             return "bar";
 *         }
 *     }
 *
 *     $object = new ExposeSample2;
 *     echo $object->ro;            //prints: foo
 *     echo $object->ro_by_getter;  //prints: bar
 * </code>
 *
 * 3) ReadWrite properties:
 * A ReadWrite property can be set and retrieved from outside class context. The advantage over simple public methods is, that
 * getter and setter methods can be defined and used. They will be called on every attempt of retrieving and settings the property.
 * A (protected) property can be made read-only by adding the Loops\Annotations\Access\ReadWrite annotation.
 * If no getter or setter is set by the annotation, the property will simply behave as a public property.
 *
 * Example:
 * <code>
 *     use Loops\Misc\AccessTrait;
 *     use Loops\Annotations\Access\ReadWrite;
 *     
 *     class ExposeSample3 {
 *         use AccessTrait;
 *
 *         /**
 *          * \@ReadWrite("setWithFoo",getter="getWithBar")
 *          {@*}
 *         protected $rw = "foo";
 *
 *         public function setWithFoo($value) {
 *             $this->rw = $value."foo";
 *         }
 *
 *         public function getWithBar() {
 *             return $this->rw."bar";
 *         }
 *     }
 *
 *     $object = new ExposeSample3;
 *     $object->rw = "test";
 *     echo $object->rw; //prints: testfoobar
 * </code>
 *
 * 4) Serializing feature:
 * tba
 *
 * 5) Smart traversal:
 * tba
 *
 * Notes:
 * - Loops\Misc\AccessTrait fully implements the ArrayAccess interface.
 * - Loops\Misc\AccessTrait fully implements the IteratorAggregate interface.
 * - Loops\Misc\AccessTrait fully implements the Serializable interface.
 * - All properties should be defined as protected in order to work without complications.
 * - Magic access (__get,__set,__isset,etc) will be forwarded to the according ArrayAccess methods where the magic happens.
 *   (If you override or define them in your class, AccessTrait functionality will not be used.)
 * - Undefined properties can be set and retrieved normally. However, they will be stored in an additional property called $__extra.
 */
trait AccessTrait {
    /**
     * Manage extra values in array - not sure if this is the best solution
     */
    protected $__extra = [];
    
    /**
     * Use the default generator for traversal
     */
    public function getIterator() {
        return $this->getGenerator();
    }
    
    /**
     * A class internal way of iterating over class properties.
     *
     * Wrapper to the AccessTrait getGenerator method.
     *
     * @param bool $include_readonly Include values that have been marked with the {\@}ReadOnly or {\@}ReadWrite annotations.
     * @param bool $include_protected Also include protected values without annotations.
     * @param array Only include values with keys that are specified in this array.
     * @param array Exclude values with keys that are specified in this array.
     * @return Generator A generator that traverses over the requested values of this object.
     */
    protected function getGenerator($include_readonly = FALSE, $include_protected = FALSE, $include = FALSE, $exclude = FALSE) {
        $pre_check = function($key) use ($include, $exclude) {
            if(is_array($include) && !in_array((string)$key, $include)) {
                return FALSE;
            }
            
            if(is_array($exclude) && in_array((string)$key, $exclude)) {
                return FALSE;
            }
            
            return TRUE;
        };
        
        $post_check = function($value) use ($include, $exclude) {
            if(is_string($include) && !($value instanceof $include)) {
                return FALSE;
            }
            
            if(is_string($exclude) && $value instanceof $exclude) {
                return FALSE;
            }
            
            return TRUE;
        };
        
        foreach(static::getExposed($include_readonly, $include_protected) as $name => $key) {
            if(!$pre_check($name)) {
                continue;
            }
            
            if(is_array($key)) {
                $value = call_user_func_array([$this, $key[0]], $key[1]);
            }
            elseif($this->offsetExists($key)) {
                $value = $this->offsetGet($key);
            }
            else {
                $value = $this->$key;
            }
            
            if(!$post_check($value)) {
                continue;
            }
            
            yield $name => $value;
        }

        foreach($this->__extra as $key => $value) {
            if(!$pre_check($key)) {
                continue;
            }
            
            yield $key => $value;
            
            if(!$post_check($value)) {
                continue;
            }
        }
    }
    
    public function serialize() {
        $property_cache = static::initPropertyCache($this);
        
        if($property_cache["sleep"] === FALSE) {
            throw new Exception("Object '".get_class($this)."' can not be serialized because it (or a subclass) implements @Sleep(forbid=true).");
        }

        $serialize = [];
        
        foreach($property_cache["sleep"] as $key) {
            $serialize[$key] = $this->$key;
        }

        return serialize($serialize);
    }
    
    public function unserialize($serialized) {
        foreach(unserialize($serialized) as $key => $value) {
            $this->$key = $value;
        }
    }
    
    /**
     * Build ReadOnly/ReadWrite/Expose/Sleep data from annotations
     */
    private static function initPropertyCache($class) {
        $loops = $class instanceof Object ? $class->getLoops() : Loops::getCurrentLoops();
        $classname = get_class($class);
        $key = "Loops-Misc-AccessTrait-$classname";
        
        static $cache;
        
        if(!$cache) {
            $cache = new ChainCache([new ArrayCache, $loops->getService("cache")]);
        }
        
        if($cache->contains($key)) {
            return $cache->fetch($key);
        }
        
        $annotations = $loops->getService("annotations");
        
        // build
        $result["getter"]    = [];
        $result["setter"]    = [];
        $result["exposed"]   = [];
        $result["protected"] = [];
        $result["sleep"]     = [];
        
        $ro = $annotations->get($classname)->properties->findFirst("Access\ReadOnly");
        $rw = $annotations->get($classname)->properties->findFirst("Access\ReadWrite");
        
        //make getter
        foreach(array_merge($ro, $rw) as $name => $annotation) {
            if($annotation->getter && !method_exists($class, $annotation->getter)) {
                throw new Exception("Passed getter '{$annotation->getter}' is not a method of class '$classname'.");
            }
            $result["getter"][$name] = $annotation->getter ? [ $annotation->getter, $annotation->arguments ] : $name;
        }
        
        //make setter
        foreach($rw as $name => $annotation) {
            if($annotation->setter && !method_exists($class, $annotation->setter)) {
                throw new Exception("Passed getter '{$annotation->setter}' is not a method of class '$classname'.");
            }
            $result["setter"][$name] = $annotation->setter ? [ $annotation->setter ] : $name;
        }

        //expose public properties
        foreach((new ReflectionClass($classname))->getProperties() as $property) {
            if($property->isStatic()) {
                continue;
            }
            
            if($property->isPublic()) {
                $name = $property->getName();
                $result["exposed"][$name] = $name;
            }
            
            if($property->isProtected()) {
                $name = $property->getName();
                $result["protected"][$name] = $name;
            }
        }
        
        //expose properties that have the Exposed annotation - prefer getter of property if exists
        foreach($annotations->get($classname)->properties->findLast("Access\Expose") as $name => $annotation) {
            $result["exposed"][$annotation->name ?: $name] = array_key_exists($name, $result["getter"]) ? $result["getter"][$name] : $name;
        }
        
        //expose methods that have the Exposed annotation
        foreach($annotations->get($classname)->methods->findLast("Access\Expose") as $name => $annotation) {
            $result["exposed"][$annotation->name ?: $name] = [ $name, $annotation->arguments ];
        }
        
        //check, which keys must be imported when sleeping
        $current = $classname;
        do {
            $set = $annotations->getStrict($current);
            
            $properties = array_keys(iterator_to_array($set->properties));
            
            $properties = array_filter($properties, function($name) use ($current) {
                $property = new ReflectionProperty($current, $name);
                if($property->isStatic()) return FALSE;
                if($property->isPrivate()) return FALSE;
                return TRUE;
            });
            
            foreach($set->find("Access\Sleep") as $sleep) {
                if($sleep->forbid) {
                    $result["sleep"] = FALSE;
                    break 2;
                }
                
                if($sleep->include) {
                    $properties = array_intersect($properties, $sleep->include);
                }
                
                if($sleep->exclude) {
                    $properties = array_diff($properties, $sleep->exclude);
                }
            }
            
            $result["sleep"] = array_merge($result["sleep"], $properties);
        } while($current = get_parent_class($current));
        
        $cache->save($key, $result);
        
        return $result;
    }
    
    private function getExposed($include_readonly = FALSE, $include_protected = FALSE) {
        $property_cache = static::initPropertyCache($this);
        $result = $property_cache["exposed"];
        
        if($include_readonly) {
            $result = array_merge($property_cache["exposed"], $property_cache["getter"]);
        }
        if($include_protected) {
            $result = array_merge($property_cache["exposed"], $property_cache["protected"]);
        }
        return $result;
    }
    
    /**
     * Retrieves a value that can be accessed by an annotation definition (("\@")ReadOnly, ("\@")ReadWrite)
     *
     * @param string $key The property name
     * @param &mixed $value The retrieved value will be stored in this reference
     * @return bool TRUE if a variable has been written into $value
     */
    public function readValue($key, &$value) {
        $property_cache = static::initPropertyCache($this);
        
        if(!array_key_exists($key, $property_cache["getter"])) {
            return FALSE;
        }
        
        $getter = $property_cache["getter"][$key];
        
        if(is_string($getter)) {
            $value = $this->$getter;
        }
        else {
            $value = call_user_func_array([$this, $getter[0]], $getter[1]);
        }
        
        return TRUE;
    }
    
    /**
     * Stores a value that can be written by an annotation definition (("\@")ReadWrite)
     *
     * @param string $key The property name
     * @param &mixed $value The value that will be stored
     * @return bool TRUE if the variable has been written successfully
     */
    public function writeValue($key, $value) {
        $property_cache = static::initPropertyCache($this);
        if(!array_key_exists($key, $property_cache["setter"])) {
            return FALSE;
        }
        
        $setter = $property_cache["setter"][$key];
        
        if(is_string($setter)) {
            $this->$key = $value;
        }
        else {
            call_user_func_array([$this, $setter[0]], [$value]);
        }
        
        return TRUE;
    }
    
    public function offsetExists($offset) {
        if(array_key_exists($offset, $this->__extra)) return TRUE;
        $property_cache = static::initPropertyCache($this);
        
        return array_key_exists($offset, $property_cache["getter"]);
    }
    
    public function offsetGet($offset) {
        if($this->readValue($offset, $value)) {
            return $value;
        }
        
        if(array_key_exists($offset, $this->__extra)) {
            return $this->__extra[$offset];
        }
        
        if(method_exists(get_parent_class($this), "offsetGet")) {
            return parent::offsetGet($offset);
        }
        
        user_error("Undefined index: $offset", E_USER_NOTICE);
    }
    
    public function offsetSet($offset, $value) {
        if($this->writeValue($offset, $value)) {
            return;
        }
        
        if(property_exists($this, $offset)) {
            throw new Exception("Failed to set property '$offset' in object '".get_class($this)."' (because it is protected or private).");
        }
        
        $this->__extra[$offset] = $value;
    }
    
    public function offsetUnset($offset) {
        unset($this->__extra[$offset]);
    }
}