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

namespace Loops\Form\Element\Validator;

use Loops;
use Loops\Form\Element;
use Loops\Form\Element\Validator;

class Length extends Validator {
    protected $min;
    protected $max;
    
    public function __construct($min = 0, $max = 0, Loops $loops = NULL) {
        parent::__construct($loops);
        $this->min = $min;
        $this->max = $max;
    }

    function validate($value, Element $element) {
        if(!is_string($value)) {
            $element->messages->add("Value must be a string.");
            return FALSE;
        }
        
        $len = mb_strlen($value);
        
        if($this->min && $this->max && ($len < $this->min || $len > $this->max)) {
            $element->messages->add("Value must have at least {$this->min} and a maximum of {$this->max} letters.");
            return FALSE;
        }
        
        if($this->min && $len < $this->min) {
            $element->messages->add("Value must have at least {$this->min} letters.");
            return FALSE;
        }
        
        if($this->max && $len > $this->max) {
            $element->messages->add("Value must have less or a maximum of {$this->max} letters.");
            return FALSE;
        }
        
        return TRUE;
    }
}