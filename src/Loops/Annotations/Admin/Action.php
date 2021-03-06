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

namespace Loops\Annotations\Admin;

/**
 * @Annotation
 * @Target({"METHOD"})
 */
class Action extends Help {
    /**
     * @var string
     * @Required
     */
    public $help;

    /**
     * @var string
     */
    public $arguments = "";

    /**
     * @var string
     */
    public $init_flags = "";
}
