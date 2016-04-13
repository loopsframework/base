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

use ReflectionClass;
use Loops;
use Loops\Service\WebCore;
use Loops\Element;
use Loops\ElementInterface;
use Loops\Annotations\Access\ReadOnly;

class Breadcrumbs extends Element {
    public function getCacheId() {
        $core = $this->getLoops()->getService("web_core");
        $pagepath = WebCore::getPagePathFromClassname(get_class($core->page), $core->page_parameter);
        return parent::getCacheId().$pagepath;
    }
    
    public function __construct(ElementInterface $context, $page_parameter = NULL, Loops $loops = NULL) {
        parent::__construct($context, $loops);

        $loops = $this->getLoops();
        $annotations = $loops->getService("annotations");
        
        if($page_parameter === NULL) {
            $page_parameter = $loops->getService("web_core")->page_parameter;
        }
        
        $reflection = new ReflectionClass(get_class($context));
        
        $entries = [];
        
        do {
            $classname = $reflection->getName();
            
            $annotation_set = $annotations->getStrict($classname);
            
            $annotation = $annotation_set->findFirst("Navigation\PageEntry");
            $breadcrumb_annotation = $annotation_set->findFirst("Navigation\Breadcrumb");
            $annotation_title = $annotation_set->findFirst("Navigation\Title");
            
            if($breadcrumb_annotation && $breadcrumb_annotation->ignore) {
                continue;
            }
            
            if($breadcrumb_annotation && $breadcrumb_annotation->title) {
                $title = $breadcrumb_annotation->title;
            }
            elseif($annotation && $annotation->title) {
                $title = $annotation->title;
            }
            elseif($annotation_title && $annotation_title->title) {
                $title = $annotation_title->title;
            }
            else {
                continue;
            }
            
            $entry = new PageEntry(substr($classname, 6), $page_parameter, $title, $loops);
            
            if($annotation && $annotation->link) {
                $entry->link = WebCore::getPagePathFromClassname("Pages\\".$annotation->link, $page_parameter);
            }

            $entries[] = $entry;
        } while($reflection = $reflection->getParentClass());
        
        foreach(array_reverse($entries) as $key => $entry) {
            $this->addChild($key, $entry);
        }
    }
    
    public function getEntries() {
        return $this->getGenerator(TRUE, TRUE, "Loops\Navigation\PageEntry");
    }
}