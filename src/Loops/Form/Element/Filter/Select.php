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
use Loops\Form\Element\Filter;

class Select extends Filter {
    private $elements;
    private $context;

    public function __construct($elements = NULL, $context = NULL, Loops $loops = NULL) {
        $this->elements = $elements;
        $this->context = $context;
        parent::__construct($loops);
    }

    public function filter($value) {
        $elements = $this->elements === NULL ? $this->context->elements : $this->elements;

        if($value === NULL && $this->context->multiple) {
            $value = [];
        }

        if(is_array($value)) {
            $value = array_intersect($value, array_keys($elements));
        }
        elseif(!in_array($value, array_keys($elements))) {
            $value = NULL;
        }

        return $value;
    }
}
