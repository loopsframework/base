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

namespace Loops\Form\Element\Filter;

use Loops;
use Loops\ArrayObject;
use Loops\Form\Element\Filter;

class ArrayObjectConverter extends Filter {
    public function filter($value) {
        if(is_array($value)) {
            $value = ArrayObject::fromArray($value);
        }
        
        if(is_null($value)) {
            $value = new ArrayObject;
        }

        return $value;
    }
}