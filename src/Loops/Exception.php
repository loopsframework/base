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

namespace Loops;

use Exception as PHPException;

/**
 * The generic Exception class that is used within Loops.
 * This class solely serves the purpose to be able to identity if an exception happened inside the Loops framework.
 */
class Exception extends PHPException {}
