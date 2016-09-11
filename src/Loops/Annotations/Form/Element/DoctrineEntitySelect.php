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

namespace Loops\Annotations\Form\Element;

use Loops;
use Loops\Annotations\Object;
use Loops\Annotations\Form\Element;
use Loops\Doctrine\FilteredEntityList;

/**
 * @Annotation
 * @Target({"ANNOTATION","PROPERTY"})
 */
class DoctrineEntitySelect extends Element {
    /**
     * @var string
     * @Required
     */
    public $entity;

    /**
     * @var string
     */
    public $class = "DoctrineEntitySelect";

    /**
     * @var integer
     */
    public $limit = 10;

    /**
     * @var string
     */
    public $alias;

    /**
     * @var array<string>
     */
    public $fields = [];

    /**
     * @var array<array<string>>
     */
    public $order = [];

    /**
     * @var string|array<string>
     */
    public $filterform_filter;

    public function factory($context = NULL, Loops $loops = NULL) {
        $entitylist = new FilteredEntityList($this->entity, $this->filterform_filter === NULL ? $this->filter : $this->filterform_filter, $this->fields, $this->limit, $this->alias, $this->order, $context, $loops);
        $this->arguments["entitylist"] = $entitylist;
        return parent::factory($context, $loops);
    }
}
