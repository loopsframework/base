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

namespace Loops\Annotations\Access;

/**
 * @Annotation
 * @Target({"PROPERTY"})
 */
class ReadWrite {
    /**
     * @var string
     */
    public $setter;
    
    /**
     * @var string
     */
    public $getter;
    
    /**
     * @var array
     */
    public $arguments = [];
}