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
use Loops\Form\Element;
use Loops\Form\Element\Filter\Text as TextFilter;

class Text extends Element {
    public $placeholder = "";

    public function __construct($default = "", $validators = [], $filters = [], $context = NULL, Loops $loops = NULL) {
        parent::__construct($default, $validators, $filters, $context, $loops);
        $this->addInternalFilter(new TextFilter($loops));
    }
}
