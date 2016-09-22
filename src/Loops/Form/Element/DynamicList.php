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
use Loops\Misc;
use Loops\Form;
use Loops\Form\Element;
use Loops\Annotations\Access\ReadOnly;

class DynamicList extends SubForm {
    private $newelement;

    /**
     * @ReadOnly("getElements")
     */
    protected $elements;

    public function __construct(Element $element, $validators = [], $filters = [], $context = NULL, Loops $loops = NULL) {
        $this->newelement = $element;
        parent::__construct(new Form, $validators, $filters, $context, $loops);
    }

    public function newelementAction($parameter) {
        if($parameter) {
            return;
        }

        $key = uniqid("e");

        $this->subform->offsetSet($key, $this->newelement);

        return $this->subform->offsetGet($key);
    }

    public function action($parameter) {
        if(count($parameter) > 1 && $parameter[0] == "subform") {
            $key = $parameter[1];

            if($key != "newelement" && !$this->subform->offsetExists($key)) {
                $this->subform->offsetSet($key, clone $this->newelement);
            }
        }

        return parent::action($parameter);
    }

    public function getElements() {
        return $this->subform->getFormElements();
    }

    public function getValue($strict = FALSE) {
        return $this->subform->getValue(TRUE);
    }

    public function setValue($value) {
        $current = $this->subform->getFormElements();

        foreach(array_keys($current) as $key) {
            $this->subform->offsetUnset($key);
        }

        foreach((array)$value as $k => $v) {
            $element = array_key_exists($k, $current) ? $current[$k] : Misc::deepClone($this->newelement);
            $element->setValue($value[$k]);
            $this->subform->offsetSet($k, $element);
        }

        return parent::setValue($value);
    }
}
