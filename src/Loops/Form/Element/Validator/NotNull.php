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

use Loops\Form\Element\Validator;
use Loops\Form\Element;

class NotNull extends Validator {
    protected $null = TRUE;
    
    function validate($value, Element $element) {
        if($value !== NULL) {
            return TRUE;
        }
        
        $element->messages->add("This field can not be NULL.");
        return FALSE;
    }
}