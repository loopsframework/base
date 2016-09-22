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
use Loops\Element;

use Loops\Annotations\Access\Expose;
use Loops\Annotations\Access\ReadOnly;
use Loops\Annotations\Access\ReadWrite;

/**
 * An entry for a Loops\Navigation
 *
 * This abstract class implements the logic for a Loops\Navigation entry.
 * An entry is defined by a link that the entry points to, a displayable
 * title and highlight logic.
 * Templates/Views should check for the $highlight property and hightlight
 * the entry if it was set to true.
 *
 */
abstract class Entry extends Element {
    /**
     * @var string $link The link for this entry.
     *
     * It can have various formats depending on the type of the entry.
     * The default template expects a relative path to the applications
     * base url.
     *
     * @Expose
     * @ReadWrite
     */
    protected $link;

    /**
     * @var string $title A displayable title for this entry
     *
     * @Expose
     * @ReadWrite
     */
    protected $title;

    private $highlightgroup;

    /**
     * @param string $link The link for this entry
     * @param string $title A displayable title for this entry
     * @param string|Loops\Page $highlightgroup If the current page inherits the page given in this parameter, $highlight will be set to TRUE
     */
    public function __construct($link, $title, $context = NULL, Loops $loops = NULL) {
        parent::__construct($context, $loops);

        $this->link           = $link;
        $this->title          = $title;
    }
}
