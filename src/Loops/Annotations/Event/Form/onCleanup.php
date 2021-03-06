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

namespace Loops\Annotations\Event\Form;

use Loops\Annotations\Listen;

/**
 * @Annotation
 * @Target({"PROPERTY","METHOD"})
 */
class onCleanup extends Listen {}
