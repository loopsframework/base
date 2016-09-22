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

namespace Loops\Annotations\Element;

use Loops\Annotations\Object;

/**
 * @Annotation
 * @Target({"ANNOTATION","PROPERTY"})
 */
class Form extends Object {
    /**
     * @var string
     * @Required
     */
    public $class;

    /**
     * @var string|bool
     */
    protected $namespace = TRUE;
}
