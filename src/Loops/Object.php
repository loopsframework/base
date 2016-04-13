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
use ArrayIterator;
use IteratorAggregate;
use Loops;
use Loops\Annotations\Access\Sleep;
use Loops\Annotations\Access\ReadOnly;
use Loops\Exception;
use Loops\Misc\EventTrait;
use Loops\Misc\AccessTrait;
use Loops\Misc\ResolveTrait;
use ReflectionProperty;
use Serializable;

/**
 * Loops Objects base class
 *
 * An object that provides magic access to services from a Loops context for convinience.
 * 
 * All common Loops traits (Event, Access, Resolve) are used.
 * When getting an offset via offsetGet, the value from ResolveTrait is prefered over a value from AccessTrait.
 * If no value could be found, Object tries to find the service at the given offset and returns it.
 *
 * The Loops object implements the ArrayAccess interface and magic functions (__get, __set, etc) are forwarded
 * to the corresponding methods.
 *
 * @Sleep(exclude={"loops"})
 */
class Object implements IteratorAggregate, ArrayAccess, Serializable {
    use EventTrait, AccessTrait, ResolveTrait {
        AccessTrait::offsetGet as AccessTraitOffsetGet;
        AccessTrait::offsetSet insteadof ResolveTrait;
        AccessTrait::offsetUnset insteadof ResolveTrait;
        AccessTrait::offsetExists as AccessTraitOffsetExists;
        ResolveTrait::offsetGet as ResolveTraitOffsetGet;
        ResolveTrait::offsetExists as ResolveTraitOffsetExists;
    }
    
    /**
     * @ReadOnly("getLoops")
     */
    protected $loops;
    
    /**
     * The contructror
     * 
     * @param Loops\Context $loops The context that is used to resolve services.
     *
     * The content will default to the last Loops context.
     */
    public function __construct(Loops $loops = NULL) {
        $this->loops = $loops ?: Loops::getCurrentLoops();
    }
    
    /**
     * Returns the Loops context associated with this object.
     *
     * @return Loops\Context The Loops context that is currently used to resolve services.
     *
     * If the Loops context is not set, which may happen after unserializing the object, the last created Loops context will be used.
     */
    public function getLoops() {
        //check for value in context
        if(!$this->loops) {
            $this->loops = Loops::getCurrentLoops();
        }
        
        return $this->loops;
    }
    
    /**
     * Gets a value either via the ResolveTrait, AccessTrait or the loops getService functionality
     *
     * Values are checked in that order. If no value existed, a notice will be generated.
     *
     * @param string $key The name of the service
     * @return mixed The requested service or NULL if not available
     */
    public function offsetGet($offset) {
        if($this->ResolveTraitOffsetExists($offset)) {
            return $this->ResolveTraitOffsetGet($offset);
        }
        
        if($this->AccessTraitOffsetExists($offset)) {
            return $this->AccessTraitOffsetGet($offset);
        }

        //return service if one exists with the requested name
        if($this->getLoops()->hasService($offset)) {
            return $this->getLoops()->getService($offset);
        }

        user_error("Undefined index: $offset", E_USER_NOTICE);
    }
    
    /**
     * Checks if an offset exists by checking the following:
     *  - check ResolveTrait if an offset exists
     *  - check AccessTrait if an offset exists
     *  - check if a service with the offset name exists in the loops object
     *
     * @param string $offset
     * @return bool TRUE if an offset exists
     */
    public function offsetExists($offset) {
        if($this->ResolveTraitOffsetExists($offset)) {
            return TRUE;
        }
        
        if($this->AccessTraitOffsetExists($offset)) {
            return TRUE;
        }
        
        return $this->getLoops()->hasService($offset);
    }
    
    /**
     * Shortcut to offsetGet
     */
    public function _($key) {
        return $this->offsetGet($key);
    }
    
    /**
     * Forward magic access to offsetSet
     */
    public function __set($key, $value) {
        $this->offsetSet($key, $value);
    }
    
    /**
     * Forward magic access to offsetGet
     */
    public function __get($key) {
        return $this->offsetGet($key);
    }
    
    /**
     * Forward magic access to offsetExists
     */
    public function __isset($key) {
        return $this->offsetExists($key);
    }
    
    /**
     * Forward magic access to offsetUnset
     */
    public function __unset($key) {
        $this->offsetUnset($key);
    }
}