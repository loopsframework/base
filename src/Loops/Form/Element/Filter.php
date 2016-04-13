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

namespace Loops\Form\Element;

use Loops\Object;

abstract class Filter extends Object {
    protected $strong = TRUE;
    
    public function prepare($value) {
        return $this->strong ? $this->filter($value) : $value;
    }
    
    abstract public function filter($value);
}