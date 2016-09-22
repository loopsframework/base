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

use Loops\Form\Element\Filter;

class Text extends Filter {
    public function filter($value) {
        if(is_array($value)) {
            $value = implode("", array_map([$this,"filter"],$value));
        }

        return (string)$value;
    }
}
