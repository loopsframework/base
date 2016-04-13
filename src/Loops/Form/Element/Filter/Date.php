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

use DateTime;

class Date extends Text {
    public function filter($value) {
        if(!($value instanceof DateTime)) {
            $value = new DateTime(parent::filter($value));
            $value->setTime(0,0,0);
        }
        
        return $value;
    }
}