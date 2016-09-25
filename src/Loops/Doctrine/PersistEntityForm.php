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

class PersistEntityForm extends EntityForm {
    public function __construct($entity, $filter = ["", "persist_entity"], $fields = [], $context = NULL, Loops $loops = NULL) {
        parent::__construct(is_string($entity) ? new $entity : $entity, $filter, $fields, $context, $loops);
    }

    /**
     * @Listen("Session\onSave")
     */
    public function detachEntityFromValue($value) {
        $entity = $value->offsetGet("value");
        $doctrine = $this->getLoops()->getService("doctrine");
        $doctrine->detach($entity);
    }

    /**
     * @Listen("Form\onSubmit")
     */
    public function persistEntity($entity) {
        $doctrine = $this->getLoops()->getService("doctrine");
        $doctrine->persist($entity);
        $doctrine->flush();
        return TRUE;
    }
}
