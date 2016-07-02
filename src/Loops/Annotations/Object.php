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

namespace Loops\Annotations;

use ReflectionClass;
use Loops;
use Loops\ArrayObject;
use Loops\Exception;
use Loops\Misc;

/**
 * @Annotation
 * @Target({"ANNOTATION","PROPERTY"})
 */
class Object {
    /**
     * @var string
     * @Required
     */
    public $class;
    
    /**
     * @Enum({"NAMESPACE","CUSTOM","CALLBACK","PROPERTY"})
     */
    public $type = "NAMESPACE";
    
    /**
     * @var array
     */
    public $arguments = [];
    
    /**
     * @var bool
     */
    public $auto_resolve = TRUE;
    
    /**
     * Set to true for context based namespace
     */
    protected $namespace = "";
    
    protected $delegate_annotation_namespace = FALSE;
    
    protected $include_in_arguments = [];
    
    protected $conflict_prefix = "%_";

    private $delegating = FALSE;
    
    public function __construct($options) {
        foreach($options as $key => $option) {
            if($key == "value") {
                $reflection = new ReflectionClass(get_class($this));
                foreach($reflection->getProperties() as $property) {
                    if(!$property->isPublic()) {
                        continue;
                    }
                    
                    $name = $property->getName();
                    
                    if(!array_key_exists($name, $options)) {
                        $key = $name;
                        break;
                    }
                }
            }

            if(property_exists($this, $key)) {
                $this->$key = $option;
                
                if(in_array($key, $this->include_in_arguments)) {
                    $this->arguments[$key] = $option;
                }
            }
            else {
                $this->arguments[$key] = $option;
            }
        }
        
        if(!$this->class) {
            throw new Exception("Target class not specified in annotation object (".get_class($this).").");
        }

        $classname = explode("\\", $this->class);
        $classname = Misc::underscore(array_pop($classname));
        $this->conflict_prefix = str_replace("%", $classname, $this->conflict_prefix);
        
        foreach($this->arguments as $key => $value) {
            if(substr($key, 0, strlen($this->conflict_prefix)) == $this->conflict_prefix) {
                $conflict = substr($key, strlen($this->conflict_prefix));
    
                if(property_exists($this, $conflict)) {
                    unset($this->arguments[$key]);
                    $this->arguments[$conflict] = $value;
                }
            }
        }
    }

    public function factory($context = NULL, Loops $loops = NULL) {
        if($this->type == "CALLBACK") {
            if(!$context) {
                throw new Exception("Context needed for callback based annotation.");
            }
            
            $callback = [ $context, $this->class ];
            
            if(!is_callable($callback)) {
                throw new Exception("Can't call method '{$this->class}' on object of class '".get_class($context)."'.");
            }
            
            $arguments = array_values(array_merge($this->arguments, [ $context, $loops ]));
            
            return call_user_func_array($callback, $arguments);
        }
        elseif($this->type == "PROPERTY") {
            if(!$context) {
                throw new Exception("Context needed for property based annotation.");
            }
            
            $key = $this->class;
            
            if($context instanceof ArrayAcces) {
                return $context->offsetGet($this->class);
            }
            elseif(property_exists($context, $this->class) || method_exists($context, "__get")) {
                return $context->$key;
            }
            
            throw new Exception("Property '$key' does not exist on object of class '".get_class($context)."'.");
        }
        elseif($this->delegate_annotation_namespace && !$this->delegating) {
            $annotation_classname = $this->delegate_annotation_namespace.$this->class;
            
            if(class_exists($annotation_classname)) {
                $this->delegating = TRUE;
                
                $vars = get_object_vars($this);
                
                $reflection = new ReflectionClass($annotation_classname);
                
                if($reflection->getConstructor()) {
                    $vars = array_merge($vars, $this->arguments);
                    $vars["arguments"] = [];
                    $annotation = $reflection->newInstance($vars);
                }
                else {
                    $annotation = $reflection->newInstance();
                    foreach($vars as $key => $value) {
                        if(!property_exists($annotation, $key)) {
                            continue;
                        }
                        $annotation->$key = $value;
                    }
                }
                
                $result = $annotation->factory($context, $loops);
                
                $this->delegating = FALSE;
                
                return $result;
            }
        }
        
        $arguments = $this->arguments;
        
        self::transformAnnotations($arguments, $context, $loops);

        $arguments["context"] = $context;
        $arguments["loops"] = $loops;
        
        return Misc::reflectionInstance($this->getClassname($context), new ArrayObject($arguments), TRUE, FALSE, [ "context", "loops" ]);
    }
    
    private static function transformAnnotations(&$arguments, $context, $loops) {
        if(!is_array($arguments)) {
            return;
        }
        
        foreach($arguments as $key => $value) {
            if($value instanceof Object) {
                $arguments[$key] = $value->factory($context, $loops);
            }
            else {
                self::transformAnnotations($arguments[$key], $context, $loops);
            }
        }
    }
    
    public function getClassname($context) {
        if($this->type == "CUSTOM") {
            return $this->class;
        }
        
        if($this->namespace === TRUE) {
            $classname = get_class($context);
            $namespace = substr($classname, 0, strrpos($classname, "\\") ?: 0);
            return $namespace."\\".$this->class;
        }
        
        return $this->namespace.$this->class;
    }
}
