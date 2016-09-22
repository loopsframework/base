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
use Loops\Doctrine\PersistEntityForm;
use Loops\Doctrine\UpdateEntityForm;

class DoctrineEntity extends SubForm {
    private $__filter;
    private $__fields;

    public function __construct($entity, $filter = "", $fields = [], $validators = [], $filters = [], $context = NULL, Loops $loops = NULL) {
        $this->__filter = $filter;
        $this->__fields = $fields;
        $form = new PersistEntityForm($entity, $filter, $fields, $context, $loops);
        parent::__construct($form, $validators, $filters, $context, $loops);
    }

    public function setValue($value) {
        if(is_object($value) && $value !== $this->value) {
            if($value->id) {
                $doctrine = $this->getLoops()->getService("doctrine");
                $doctrine->merge($value);
                $this->setForm(new UpdateEntityForm($value, $this->__filter, $this->__fields, $context, $loops));
            }
            else {
                $this->setForm(new PersistEntityForm($value, $this->__filter, $this->__fields, $context, $loops));
            }
        }

        return parent::setValue($value);
    }
}
