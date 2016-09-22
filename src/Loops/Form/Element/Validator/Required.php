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
use Loops\Annotations\Access\ReadWrite;
use Loops\Form\Element;
use Loops\Form\Element\Validator;

class Required extends Validator {
    protected $last = TRUE;
    protected $null = TRUE;

    /**
     * @ReadWrite
     */
    protected $message;

    function validate($value, Element $element) {
        if(is_scalar($value) && strlen((string)$value)) {
            return TRUE;
        }

        if($value) {
            return TRUE;
        }

        $element->messages->add($this->message ?: "This field is required.");

        return FALSE;
    }
}
