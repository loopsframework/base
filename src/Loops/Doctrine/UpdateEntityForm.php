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

namespace Loops\Doctrine;

use Loops;
use Loops\Annotations\Listen;

class UpdateEntityForm extends EntityForm {
    public function __construct($entity, $filter = ["", "update_entity"], $fields = [], $context = NULL, Loops $loops = NULL) {
        parent::__construct($entity, $filter, $fields, $context, $loops);
    }

    /**
     * @Listen("Form\onSubmit")
     */
    public function updateEntity($entity) {
        $doctrine = $this->getLoops()->getService("doctrine");
        $doctrine->merge($entity);
        $doctrine->flush();
        return TRUE;
    }
    
    /**
     * @Listen("Session\onSave")
     */
    public function detachEntity($value) {
        $doctrine = $this->getLoops()->getService("doctrine");
        $doctrine->detach($value->value);
    }
}