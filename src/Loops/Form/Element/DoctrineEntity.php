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

class DoctrineEntity extends SubForm {
    public function __construct($entity, $filter = "", $fields = [], $validators = [], $filters = [], $context = NULL, Loops $loops = NULL) {
        $form = new PersistEntityForm($entity, $filter, $fields, $context, $loops);
        parent::__construct($form, $validators, $filters, $context, $loops);
    }
}
