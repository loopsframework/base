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

namespace Loops\Renderer;

use Loops\Exception;
use Loops\ElementInterface;

class RenderException extends Exception {
    public $object;
    public $custom_template;
    public $proposed_template_names = [];
    public $appearances             = [];
    public $forced_appearances      = [];
    
    public function __construct($message, $object, array $appearances = [], array $forced_appearances = []) {
        $this->object = $object;
        $this->appearances = $appearances;
        $this->forced_appearances = $forced_appearances;
        
        //make a list of proposed template files - does not list up template names based on object inheritance
        $appearances = array_unique(array_merge($appearances, $forced_appearances));
        
        $parts = [];
        $classes = [];
        
        $this->custom_template = ($object instanceof CustomizedRenderInterface) ? $object->getTemplateName() : NULL;
        
        if($this->custom_template) {
            $classes = [ $this->custom_template ];
        }
        else {
            $delegate = ($object instanceof CustomizedRenderInterface) ? $object->delegateRender() : NULL;
            
            if($object instanceof ElementInterface) {
                $child = NULL;
                do {
                    if($child) {
                        array_unshift($parts, $child->getName());
                        array_unshift($classes, str_replace("\\", "/", strtolower(get_class($object))));
                    }
                    else {
                        array_unshift($classes, str_replace("\\", "/", strtolower(get_class($delegate?:$object))));
                    }
                    
                    $child = $object;
                } while($object = $object->getParent());
            }
            else {
                array_unshift($classes, str_replace("\\", "/", strtolower(is_object($object) ? get_class($object) : gettype($object))));
            }
        }
        
        foreach($classes as $class) {
            for($i=0;$i<=count($appearances);$i++) {
                $base = implode("-", array_merge([$class], $parts));
                $proposed_appearances = array_unique(array_merge(array_slice($appearances, 0, $i), $forced_appearances));
                $name = implode(".", array_merge([$base], $proposed_appearances, [ "smarty" ]));
                $this->proposed_template_names[] = $name;
            }
            array_shift($parts);
        }
        
        $this->proposed_template_names = array_unique($this->proposed_template_names);
        
        parent::__construct($message);
    }
}