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

use ArrayAccess;
use Loops;
use Loops\Form;
use Loops\Form\Element;
use Loops\Form\Element\Filter\ArrayObjectConverter;
use Loops\Annotations\Listen;
use Loops\Annotations\Access\ReadOnly;
use Loops\Annotations\Access\Expose;

class Subform extends Element {
    /**
     * @ReadOnly
     * @Expose
     */
    protected $subform;

    public function __construct(Form $form, $validators = [], $filters = [], $context = NULL, Loops $loops = NULL) {
        $this->setForm($form);
        parent::__construct($this->subform->getValue(), $validators, $filters, $context, $loops);
        $this->addInternalFilter(new ArrayObjectConverter($this->getLoops()));
    }

    /**
     * @Listen("Form\onValidate")
     */
    public function subformValidate($value) {
        return $this->subform->validate();
    }

    /**
     * @Listen("Form\onCleanup")
     */
    public function doCleanup() {
        $this->subform->fireEvent("Form\onCleanup", [$this->subform]);
    }

    public function setForm(Form $form) {
        $this->subform = $this->initChild("subform", $form);
        $this->subform->weak = TRUE;
        $this->value = $this->subform->getValue();
        $this->default = $this->value;
    }

    public function setValue($value) {
        parent::setValue($value);

        $subform_value = $this->subform->getValue();

        foreach($this->subform->getFormElements() as $name => $child) {
            $child->setValue($this->value->offsetExists($name) ? $this->value->offsetGet($name) : NULL);
            $subform_value->offsetSet($name, $child->getValue(FALSE));
        }

        return $this->value = $subform_value;
    }

    public function getFormValue($strict = FALSE) {
        return $this->subform->getFormValue($strict);
    }

    public function offsetExists($offset) {
        return parent::offsetExists($offset) ?: $this->subform->offsetExists($offset);
    }

    public function offsetGet($offset) {
        return parent::offsetExists($offset) ? parent::offsetGet($offset) : $this->subform->offsetGet($offset);
    }
}
