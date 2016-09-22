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

class Number extends Filter {
    protected $strong = FALSE;

    public function filter($value) {
        return (int)$value;
    }
}
