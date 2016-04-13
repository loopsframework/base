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

use Loops\Annotations\Form\Validator as ValidatorAnnotation;

class RegExp extends Validator {
    /**
     * @ReadWrite
     */
    protected $message;
    
    /**
     * @ReadWrite
     */
    protected $message_notstring;
    
    protected $expr;
    
    public function __construct($expr, Loops $loops = NULL) {
        parent::__construct($loops);
        $this->expr = $expr;
    }
    
    function validate($value, Element $element) {
        if(!is_string($value)) {
            $element->messages->add($this->message_notstring ?: "Value must be a string.");
            return FALSE;
        }
        
        if(!preg_match($this->expr, $value)) {
            $element->messages->add(sprintf($this->message ?: "Value must match expression '%s'.", $this->expr));
            return FALSE;
        }
        
        return TRUE;
    }
}