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

namespace Loops\Annotations\Navigation;

/**
 * @Annotation
 * @Target({"CLASS"})
 */
class PageEntry {
    /**
     * @var string
     */
    public $title;

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $filter;

    /**
     * @var string
     */
    public $link;

    /**
     * @var string
     */
    public $highlight;

    /**
     * @var integer
     */
    public $order = 0;
}
