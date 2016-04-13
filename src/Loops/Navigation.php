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

use Loops;
use Loops\Service\WebCore;
use Loops\Navigation\Entry;
use Loops\Navigation\PageEntry;
use Loops\Navigation\SubNavigation;
use ReflectionClass;

/**
 * The loops navigation menu
 *
 * The navigation menu can be used to render a navigation menu with the currently
 * active page beeing highlighted.
 * It is possible to highlight an entry on multiple pages. For details look at the
 * documentation of Loops\Navigation\Entry.
 *
 * <code>
 *      $sub = new Navigation;
 *      $sub->addEntry(new Link("http://www.example.com/external/link"));
 *      $sub->addEntry(new PageEntry("Index")); //index in submenu
 *
 *      $this->navigation = new Navigation;
 *      $this->navigation->addEntry(new SubNavigation($sub, "Submenu"));
 *      $this->navigation->addEntry(new PageEntry("Index"));
 * </code>
 *
 * @link tba - navigation manual
 * @package loops
 */
class Navigation extends Element {
    protected $ajax_access = TRUE;
    
    public function __construct($sitemap = TRUE, $filter = NULL, $desingated_parameter = [], $context = NULL, Loops $loops = NULL) {
        parent::__construct($context, $loops);
        
        if($sitemap) {
            foreach(self::createSiteMap($filter, $desingated_parameter, $sitemap, $loops) as $key => $entry) {
                $this->offsetSet($key, $entry);
            }
        }
    }
    
    public function getCacheId() {
        return parent::getCacheId().get_class($this->getLoops()->getService("core")->page);
    }
    
    public function getEntries() {
        return $this->getGenerator(TRUE, TRUE, "Loops\Navigation\Entry");
    }
    
    public function addEntry($key, Entry $entry) {
        return $this->offsetSet($key, $entry);
    }
    
    public static function createSiteMap($filter = NULL, $designated_parameter = [], $object_filter = NULL, Loops $loops = NULL) {
        if(!$loops) {
            $loops = Loops::getCurrentLoops();
        }

        $annotations = $loops->getService("annotations");

        $classnames = $loops->getService("application")->definedClasses();
        
        if($object_filter) {
            if(is_object($object_filter)) {
                $object_filter = get_class($object_filter);
            }

            if(is_string($object_filter)) {
                $classnames = array_filter($classnames, function($classname) use ($object_filter) { return is_a($classname, $object_filter, TRUE); });
            }
        }
        
        $entries = [];
        
        foreach($classnames as $classname) {
            if(!$annotation = $annotations->getStrict($classname)->findFirst("Navigation\PageEntry")) {
                continue;
            }
            
            if($filter != $annotation->filter) {
                continue;
            }
            
            $highlight = $annotation->highlight ? "Pages\\".$annotation->highlight : NULL;

            $link = WebCore::getPagePathFromClassname($annotation->link ? "Pages\\".$annotation->link : $classname, $designated_parameter);

            if($link !== FALSE) {
                $entry = new PageEntry(substr($classname, 6), $designated_parameter, $annotation->title, $loops);
                
                if($annotation->link) {
                    $entry->link = WebCore::getPagePathFromClassname("Pages\\".$annotation->link, $designated_parameter);
                }
                
                $entries[] = [ $classname, $annotation, $entry ];
            }
        }
        
        if($entries) {
            usort($entries, function($a, $b) {
                if($result = count(class_parents($a[0])) - count(class_parents($b[0]))) return $result;
                if($result = $a[1]->order - $b[1]->order) return $result;
                if($result = strcmp($a[1]->name, $b[1]->name)) return $result;
                if($result = strcmp($a[1]->title, $b[1]->title)) return $result;
                if($result = strcmp($a[0], $b[0])) return $result;
                return 0;
            });
            
            self::groupEntries($entries, $loops);
        }
        
        $result = [];
        
        foreach($entries as $key => $entry) {
            $result[$entry[1]->name ?: $key] = $entry[2];
        }
        
        return $result;
    }
    
    private static function groupEntries(&$entries, $loops) {
        $result = [];
        
        do {
            $entry = array_shift($entries);
            
            if($subentries = self::extractEntries($entries, $entry[0])) {
                self::groupEntries($subentries, $loops);
                
                $navigation = new Navigation(FALSE);
                foreach($subentries as $key => $subentry) {
                    $navigation->offsetSet($subentry[1]->name ?: $key, $subentry[2]);
                }
                
                $entry[2] = new SubNavigation($navigation, $entry[2], "", NULL, $loops);
            }
            
            $result[] = $entry;
        } while($entries);
        
        $entries = $result;
    }
    
    private static function extractEntries(&$entries, $classname) {
        $result = [];
        
        foreach($entries as $key => $entry) {
            if(in_array($classname, class_parents($entry[0]))) {
                $result[] = $entry;
                unset($entries[$key]);
            }
        }
        
        return $result;
    }
}