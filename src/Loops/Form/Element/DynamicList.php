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

use Traversable;
use Loops;
use Loops\ArrayObject;
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
                // Clone $this->newelement should be used but shallow cloning
                // will break loopsid functionality. Implementing deep cloning is a
                // tough task for Loops\Element objects, maybe consider it later
                $this->subform->offsetSet($key, $this->newelement);
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
        if(is_array($value)) {
            $value = new ArrayObject($value);
        }

        $current = $this->subform->getFormElements();

        foreach(array_keys($current) as $key) {
            $this->subform->offsetUnset($key);
        }

        if($value instanceof ArrayObject) {
            $values = $value->toArray();
        }
        elseif($value instanceof Traversable) {
            $values = iterator_to_array($value);
        }
        else {
            $values = (array)$value;
        }

        static $meh = 0;
        if($meh++ > 40) {
            throw new \Exception("Meh");
        }

        foreach($values as $k => $v) {
            $element = array_key_exists($k, $current) ? $current[$k] : Misc::deepClone($this->newelement);
            $element->setValue($v);
            $value->offsetSet($k, $element->getValue(FALSE));
            $this->subform->offsetSet($k, $element);
        }

        return parent::setValue($value);
    }
}
