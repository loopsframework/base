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
use Loops\Exception;
use Loops\Service\WebCore;
use Loops\Annotations\Access\Expose;
use Loops\Annotations\Access\ReadOnly;

/**
 * A Loops\Navigation entry that points to a page
 *
 * See constructor for details.
 * If no title is specified, annotations can be used.
 * This class looks for the argument 'title' in the first 'Navigation' entry
 * from class annotations.
 *
 * <code>
 *      namespace Pages;
 *
 *      /**
 *       * \@Navigation(title="MyIndex")
 *       {@*}
 *      class Index extends \Loops\Page {
 *      }
 *
 *      $entry1 = new Loops\Navigation\PageEntry("Index");
 *      $entry2 = new Loops\Navigation\PageEntry("Page", "MyAbstractPage", "CustomTitle");
 * </code>
 */
class PageEntry extends Entry {
    /**
     * @var bool $highlight This value will be set to TRUE if this entry should be highlighted
     *
     * @Expose
     * @ReadOnly
     */
    protected $highlight = FALSE;
    
    /**
     * @ReadOnly
     */
    protected $pageclass;
    
    /**
     * @param string|object $page The page where this navigation entry points to
     * @param string|object $highlightgroup If the page inherits from this class/object, the entry will be highlighted. Defaults to $page if not specified.
     * @param string $title The title for this entry. Defaults to classname of page if not found in annotations.
     */
    public function __construct($page, $page_parameter = NULL, $title = "", Loops $loops = NULL) {
        $prefix = "Pages\\";
        
        if(!$loops) {
            $loops = Loops::getLoops();
        }
        
        $core = $loops->getService("web_core");
        
        $this->pageclass = $pageclass = is_object($page) ? get_class($page) : $prefix.$page;

        if(!class_exists($pageclass)) {
            throw new Exception("Class '$pageclass' does not exist.");
        }

        $count = WebCore::getParameterCount($pageclass);

        if($page_parameter === NULL) {
            $page_parameter = array_slice($core->page_parameter, 0, $count);
        }
        
        if(!$title) {
            if($annotation = $loops->getService("annotations")->get($this->pageclass)->findFirst("Navigation\Title")) {
                $title = $annotation->title;
            }
        }

        if(!$title) {
            if(is_object($page)) {
                $title = get_class($page);
                if(substr($title, 0, strlen($prefix)) == $prefix) {
                    $title = substr($title, strlen($prefix));
                }
            }
            else {
                $title = $page;
            }
        }
            
        $link = WebCore::getPagePathFromClassname($pageclass, $page_parameter);
        
        parent::__construct($link, $title, $loops);
        
        $this->highlight = is_a($core->page, $pageclass, TRUE) && implode("/", array_slice($core->page_parameter, 0, $count)) == implode("/", array_slice($page_parameter, 0, $count));
    }
}