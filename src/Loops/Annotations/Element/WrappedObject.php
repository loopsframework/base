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

use Loops;
use Loops\Annotations\Object;
use Loops\Misc\WrappedObject as MiscWrappedObject;

/**
 * @Annotation
 * @Target({"ANNOTATION","PROPERTY"})
 */
class WrappedObject extends Object {
    public function factory($context = NULL, Loops $loops = NULL) {
        return new MiscWrappedObject(parent::factory($context, $loops), $loops);
    }
}