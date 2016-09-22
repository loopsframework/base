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

use Loops;
use Loops\Object;
use Loops\Exception;

/**
 * A trait which automatically loads objects into values by annotations
 *
 * Properties can be annotated with the "Loops\Annotations\Object" annotation or any child
 * class of it (as long as the 'auto_resolve' property is set to TRUE).
 * The resolve trait fully implements the ArrayAccess interface and when an annotated property
 * is accessed via the interface, it will be automatically instantiated. This happens only on
 * the first attemt to access the value, subsequent access will return the same instance of the
 * object. (Lazy loading)
 *
 * See the documentations of "Loops\Annotations\Object" for details on how to define object annotations.
 * The object will be instantiated by calling the factory method of the annotation.
 * The $context argument is set to this object, while the $loops argument is set to this objects $loops
 * context if it is an instance of "Loops\Object". Otherwise the current global context is used.
 *
 * Note that due to the access scope of this trait, protected properties will also
 * be instantiated and returned. Use of private properties is not recommended although it may work in some
 * cases.
 *
 * Example:
 * A DateTime object if property "date" is accessed.
 * <code>
 *     use Loops\Annotations\Object;
 *     use Loops\Misc\ResolveTrait;
 *
 *     class Test implements ArrayAccess {
 *         use ResolveTrait;
 *
 *         /**
 *          * ("\@")Object("DateTime")
 *          {@*}
 *         public $date;
 *     }
 *
 *     $a = new Test;
 *     echo $a["date"]->format("r");
 * </code>
 *
 * Example 2:
 * For more synthetic sugar, the magic __get method can be defined.
 * Note that the DateTime object is lazily instantiated on first access of the date property.
 * <code>
 *     use Loops\Annotations\Object;
 *     use Loops\Misc\ResolveTrait;
 *
 *     class Test implements ArrayAccess {
 *         use ResolveTrait;
 *
 *         /**
 *          * ("\@")Object("DateTime")
 *          {@*}
 *         protected $date;
 *
 *         public function __get($key) {
 *             return $this->offsetGet($key);
 *         }
 *     }
 *
 *     $a = new Test;
 *     echo $a->date->format("r");
 * </code>
 *
 * Example 3:
 * The Loops\Object class uses ResolveTrait and forwards magic __get access similar to Example 2.
 *
 * <code>
 *     use Loops\Object as LoopsObject;
 *     use Loops\Annotations\Object;
 *
 *     class Test extends LoopsObject {
 *         /**
 *          * ("\@")Object("DateTime")
 *          {@*}
 *         protected $date;
 *     }
 *
 *     $a = new Test;
 *     echo $a->date->format("r");
 * </code>
 */
trait ResolveTrait {
    /**
     * Checks if a resolvable property has been defined.
     *
     * @param string $offset The requested offset (property) name
     * @return bool TRUE if a resolveable value exists at the offset
     */
    public function offsetExists($offset) {
        return (bool)static::__ResolveTrait_hasObjectAnnotation($this, $offset);
    }

    /**
     * Returns the property at the offset.
     *
     * If the property is set to NULL, it will be instantiated by the annotation.
     * A notice is issued when trying to access a missing property or when no annotation
     * was found.
     *
     * @param string $offset The requested offset (property) name
     * @return mixed The value of the property at the offset after instanciation
     */
    public function offsetGet($offset) {
        if($pair = static::__ResolveTrait_hasObjectAnnotation($this, $offset)) {
            if($this->$offset === NULL && $pair[0]->auto_resolve) {
                $this->$offset = $pair[0]->factory($this, $pair[1]);
            }

            return $this->$offset;
        }

        user_error("Undefined index: $offset", E_USER_NOTICE);
    }

    /**
     * Setting values is not supported and will lead to an exception
     *
     * @param string $offset The requested offset (property) name
     * @throws Loops\Exception
     */
    public function offsetSet($offset, $value) {
        throw new Exception("ResolveTrait does not support setting of values. (Tried to set '$offset' of object '".get_class($this)."')");
    }

    /**
     * Unsets an offset so it can be instantiated again
     *
     * On the next access, the object will be instantiated again with the factory method of the annotation
     * as at the first access.
     *
     * @param string $offset The requested offset (property) name
     */
    public function offsetUnset($offset) {
        if($pair = static::__ResolveTrait_hasObjectAnnotation($this, $offset)) {
            $this->$offset = NULL;
        }
    }

    private static function __ResolveTrait_hasObjectAnnotation($class, $offset) {
        $loops = $class instanceof Object ? $class->getLoops() : Loops::getCurrentLoops();

        if(!$property = $loops->getService("annotations")->get($class)->properties->$offset) {
            return FALSE;
        }

        if(!$annotation = $property->findFirst("Object")) {
            return FALSE;
        }

        return [ $annotation, $loops ];
    }
}
