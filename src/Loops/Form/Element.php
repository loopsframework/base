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

namespace Loops\Form;

use ReflectionClass;
use Loops;
use Loops\Annotations\Access\Expose;
use Loops\Annotations\Access\ReadOnly;
use Loops\Annotations\Access\ReadWrite;
use Loops\Annotations\Form\Element as ElementAnnotation;
use Loops\Exception;
use Loops\Form;
use Loops\Element as LoopsElement;
use Loops\Form\Element;
use Loops\Form\Element\Validator;
use Loops\Form\Element\Filter;
use Loops\Messages\Message;
use Loops\Messages\MessageList;

abstract class Element extends LoopsElement {
    /**
     * @ReadWrite
     * @Expose
     */
    protected $label;
    
    /**
     * @ReadWrite
     * @Expose
     */
    protected $description;
    
    /**
     * @ReadOnly
     * @Expose
     */
    protected $messages;
    
    /**
     * @ReadWrite
     * @Expose
     */
    protected $nullable = FALSE;
    
    /**
     * @ReadOnly("getFormName")
     * @Expose
     */
    protected $name;
    
    /**
     * @var array
     */
    protected $null_if = [ "" ];
    
    /**
     * @ReadOnly
     */
    protected $validators = [];
    
    /**
     * @ReadOnly
     */
    protected $filters = [];
    
    protected $internal_filters = [];
    
    protected $value;
    
    protected $default;
    
    public function __construct($default = NULL, $validators = [], $filters = [], $context = NULL, Loops $loops = NULL) {
        parent::__construct($context, $loops);
        
        $this->default = $default;
        $this->setValue($default);
        $this->messages = new MessageList(Message::ERR);

        foreach($validators as $validator) {
            $this->addValidator($validator);
        }

        foreach($filters as $filter) {
            $this->addFilter($filter);
        }
    }
    
    public function getFormName() {
        $parts   = [];
        $element = $this;
        
        while($parent = $element->getParent()) {
            if($element instanceof Element) {
                array_unshift($parts, $element->getName());
            }
            
            if($element instanceof Form && !$element->weak) {
                break;
            }
            
            $element = $parent;
        };
        
        return implode("-", $parts);
    }
    
    public function addValidator(Validator $validator, $before = FALSE) {
        if($before) {
            array_unshift($this->validators, $validator);
        }
        else {
            array_push($this->validators, $validator);
        }
    }
    
    public function addFilter(Filter $filter, $before = FALSE) {
        if($before) {
            array_unshift($this->filters, $filter);
        }
        else {
            array_push($this->filters, $filter);
        }
    }
    
    protected function addInternalFilter(Filter $filter, $before = FALSE) {
        if($before) {
            array_unshift($this->internal_filters, $filter);
            
        }
        else {
            array_push($this->internal_filters, $filter);
        }
    }
    
    public function setValue($value) {
        if($this->nullable && in_array($value, $this->null_if, TRUE)) {
            $value = NULL;
        }
        
        foreach(array_merge($this->internal_filters, $this->filters) as $filter) {
            if($this->nullable && $value === NULL) {
                break;
            }
            
            $value = $filter->prepare($value);
        }
        
        return $this->value = $value;
    }
    
    public function getDefault() {
        return $this->default;
    }
    
    /**
     * @Expose(name="value")
     */
    public function getValue($strict = FALSE) {
        if(!$strict) {
            return $this->value;
        }
        
        if($this->nullable && $this->value === NULL) {
            return NULL;
        }

        $value = $this->value;
        
        foreach(array_merge($this->internal_filters, $this->filters) as $filter) {
            $value = $filter->filter($value);
        }
        
        return $value;
    }
    
    /**
     * @todo last,break handling with annotated validators
     */
    public function validate() {
        if($this->nullable && $this->value === NULL) {
            $validators = array_filter($this->validators, function($validator) { return $validator->validateNull(); });
        }
        else {
            $validators = $this->validators;
        }
        
        $value = $this->getValue();

        $result = $this->fireEvent("Form\onValidate", [ $value, $this ], TRUE);

        foreach($validators as $validator) {
            $result = (bool)($result & $validator->validate($value, $this));
            
            if($result ? $validator->doBreak() : $validator->isLast()) {
                break;
            }
        }
        
        return $result;
    }
}