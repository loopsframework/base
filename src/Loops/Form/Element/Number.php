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
use Loops\Form\Element\Text;
use Loops\Form\Element\Validator\Number as NumberValidator;
use Loops\Form\Element\Filter\Number as NumberFilter;
use Loops\Form\Element\Filter\Text as TextFilter;

class Number extends Text {
    public $null_if = [ "" ];

    public function __construct($default = NULL, $validators = [], $filters = [], $context = NULL, Loops $loops = NULL) {
        parent::__construct($default, $validators, $filters, $context, $loops);
        $this->addInternalFilter(new NumberFilter($loops));
        $this->addValidator(new NumberValidator($loops));
    }
}
