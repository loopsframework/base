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

namespace Loops\Navigation;

use Loops;

/**
 * An external link entry for Loops\Navigation
 *
 * External links can not be highlighted (as it would not make sense).
 */
class Link extends Entry {
    /**
     * @var bool $newtab Specifies if the link should be opened in a new tab
     */
    public $newtab;

    /**
     * @param string $link The external link as it should be shown in href
     * @param string $title A displayable title for this entry
     * @param bool $newtab Specifies if the link should be opened in a new tab
     */
    public function __construct($link, $title = "", $newtab = TRUE, Loops $loops = NULL) {
        $this->newtab = $newtab;

        parent::__construct($link, $title ?: $link, NULL, $context);
    }
}
