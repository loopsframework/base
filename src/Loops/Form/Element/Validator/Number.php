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

use Loops\Form\Element;
use Loops\Form\Element\Validator;

class Number extends Validator {
    function validate($value, Element $element) {
        if(is_numeric($value)) {
            return TRUE;
        }

        $element->messages->add("Value is not numeric.");
        return FALSE;
    }
}
