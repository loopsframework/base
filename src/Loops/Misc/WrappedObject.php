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

use IteratorAggregate;
use AppendIterator;
use ArrayIterator;
use ArrayAccess;
use Loops;
use Loops\Exception;
use Loops\Element;
use Loops\Renderer\CustomizedRenderInterface;
use Loops\Annotations\Access\Sleep;

/**
 * This class wraps an arbitrary object. It will forward all ArrayAccess
 * requests, calls and get/set requests to the wrapped object.
 * The wrapper inherits from "Loops\Element" and provides its functionality.
 * The whole point of using this class is the possibility to assign a Loops
 * id (and child objects) to non-Loops objects.
 * When iterated, the values from "Loops\Element" are added to the values of
 * the wrapped object.
 * When beeing rendered, the wrapped object will be displayed instead of this
 * object. This is implemented by the
 * Loops\Renderer\CustomizedRenderInterface.
 *
 * @Sleep({"wrapped_object"})
 */
class WrappedObject extends Element implements ArrayAccess, CustomizedRenderInterface {
    protected $wrapped_object;
    
    public function __construct($object, $context = NULL, Loops $loops = NULL) {
        parent::__construct($context, $loops);
        $this->wrapped_object = $object;
    }
    
    /**
     * Returns an AppendIterator that iterates over both the wrapped object and this object
     */
    public function getIterator() {
        $iterator = new AppendIterator;
        
        if($this->wrapped_object instanceof IteratorAggregate) {
            $iterator->append($element->getIterator());
        }
        else {
            $iterator->append(new ArrayIterator($this->wrapped_object));
        }
        $iterator->append(parent::getIterator());
        return $iterator;
    }
    
    public function __isset($name) {
        if(parent::offsetExists($offset)) {
            return TRUE;
        }
        
        return isset($this->wrapped_object->$name);
    }
    
    public function __get($name) {
        if(parent::offsetExists($offset)) {
            return parent::offsetGet($offset);
        }
        
        return $this->wrapped_object->$name;
    }
    
    public function __set($name, $value) {
        if($this->writeValue($offset, $value)) {
            return;
        }
        
        $this->wrapped_object->$name = $value;
    }
    
    public function __unset($name) {
        unset($this->wrapped_object->$name);
    }
    
    public function __call($name, $arguments) {
        return call_user_func_array([$this->wrapped_object, $name], $arguments);
    }
    
    public function offsetExists($offset) {
        if(parent::offsetExists($offset)) {
            return TRUE;
        }
        
        if($this->wrapped_object instanceof ArrayAccess) {
            return $this->wrapped_object->offsetExists($offset);
        }
        
        return FALSE;
    }
    
    public function offsetGet($offset) {
        if(parent::offsetExists($offset)) {
            return parent::offsetGet($offset);
        }
        
        if($this->wrapped_object instanceof ArrayAccess) {
            return $this->wrapped_object->offsetGet($offset);
        }
    }
    
    public function offsetSet($offset, $value) {
        if($this->writeValue($offset, $value)) {
            return;
        }
        
        if($this->wrapped_object instanceof ArrayAccess) {
            $this->wrapped_object->offsetSet($offset, $value);
        }
    }
    
    public function offsetUnset($offset) {
        if($this->wrapped_object instanceof ArrayAccess) {
            $this->wrapped_object->offsetUnset($offset);
        }
    }
    
    public function delegateRender() {
        return $this->wrapped_object;
    }
    
    public function getTemplateName() {}
    public function modifyAppearances(&$appearances, &$forced_appearances) {}
}