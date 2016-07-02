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
class PersistEntityForm extends Object {
    /**
     * @var string
     * @Required
     */
    public $entity;
    
    /**
     * @var string 
     */
    public $class = "Loops\Doctrine\PersistEntityForm";
    
    /**
     * @var array<string>
     */
    protected $include_in_arguments = [ "entity" ];
}