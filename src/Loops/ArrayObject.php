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
use ArrayObject as StdArrayObject;

/**
 * An extension to the PHP Array object that implements some more features.
 */
class ArrayObject extends StdArrayObject {
    public function __construct($array = [], $flags = StdArrayObject::ARRAY_AS_PROPS, $iterator_class = "ArrayIterator") {
        parent::__construct($array, $flags, $iterator_class);
    }
    
    /**
     * Merges values from another Loops\ArrayObject into this one by keys
     *
     * @param Loops\ArrayObject $other Merge values from this array object
     * @param bool $recursive Recursively merge values (i.e. if both values are of type Loops\ArrayObject)
     */
    public function merge(ArrayObject $other, $recursive = TRUE) {
        foreach($other as $key => $value) {
            if($recursive && $this->offsetExists($key)) {
                $ownvalue = $this->offsetGet($key);
                if($ownvalue instanceof ArrayObject && $value instanceof ArrayObject) {
                    $ownvalue->merge($value, TRUE);
                    continue;
                }
            }
            
            $this->offsetSet($key, $value);
        }
        
        return $this;
    }
    
    /**
     * Returns the keys and values of this object as a standard array
     *
     * @return array<mixed> The keys and values as an array
     */
    public function toArray() {
        $result = iterator_to_array($this);
        foreach($result as $key => &$value) {
            if($value instanceof ArrayObject) {
                $value = $value->toArray();
            }
        }
        
        return $result;
    }
    
    /**
     * Creates an "Loops\ArrayObject" from a normal PHP array.
     * Nested arrays will recursively converted.
     *
     * @param array $input The input array
     * @return Loops\ArrayObject The input array as a "Loops\ArrayObject".
     */
    public static function fromArray(array $input) {
        $input = array_map(function($value) {
            return is_array($value) ? ArrayObject::fromArray($value) : $value;
        }, $input);
        
        return new ArrayObject($input);
    }
}