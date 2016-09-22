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

namespace Loops\Annotations\Form;

use Loops\Annotations\Object;

/**
 * @Annotation
 * @Target({"ANNOTATION","PROPERTY"})
 */
class Element extends Object {
    /**
     * @var string
     * @Required
     */
    public $class;

    /**
     * @var string|array<string>
     */
    public $filter = [ "" ];

    /**
     * @var bool
     */
    public $auto_resolve = FALSE;

    /**
     * @var string
     */
    protected $namespace = "Loops\Form\Element\\";

    /**
     * @var string
     */
    protected $delegate_annotation_namespace = "Loops\Annotations\Form\Element\\";
}
