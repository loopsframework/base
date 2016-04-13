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

use Loops;
use Loops\Exception;
use Loops\Object;
use Loops\Form\Element;
use Loops\Misc\AccessTrait;
use Loops\Annotations\Form\Validator as ValidatorAnnotation;

abstract class Validator extends Object {
    use AccessTrait;
    
    protected $last = FALSE;
    protected $break = FALSE;
    protected $null = FALSE;
    
    public function isLast() {
        return $this->last;
    }
    
    public function doBreak() {
        $b = "break";
        return $this->$b;
    }

    public function validateNull() {
        return $this->null;
    }
    
    abstract function validate($value, Element $element);
}