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
use Loops\Navigation;

/**
 * A subnavigation for a Loops\Navigation
 *
 * Enables sub entries in an entry.
 * The default templtae/view only supports presentation up to one sublevel due to
 * limitations in twitter bootstrap.
 * For more deeply nested menus, provide your own templates/views.
 */
class SubNavigation extends Entry {
    /**
     * @param Loops\Navigation $navigation The subnavigtaion menu
     * @param string|Loops\Navigation\Entry $title A displayable title for this entry OR an entry from which link, title and highlight will be inherited
     * @param string $link The link for this entry (may not be clickable depending on template implementation)
     */
    public function __construct(Navigation $navigation, $title, $link = "", Loops $loops = NULL) {
        $this->subnavigation = $navigation;

        if($title instanceof Entry) {
            parent::__construct($title->link, $title->title, NULL, $loops);
            $this->entry     = $title;
            $this->link      = &$this->entry->link;
            $this->title     = &$this->entry->title;
        }
        else {
            parent::__construct($link, $title, $loops);
        }
    }
}