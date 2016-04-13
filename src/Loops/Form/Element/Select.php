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

namespace Loops\Form\Element;

use Loops;
use Loops\Form\Element;
use Loops\Form\Element\Filter\Select as SelectFilter;
use Loops\Annotations\Access\ReadOnly;
use Loops\Annotations\Access\Expose;

class Select extends Element {
    /**
     * @ReadOnly
     * @Expose
     */
    protected $elements;
    
    /**
     * @ReadOnly
     * @Expose
     */
    protected $multiple;
    
    public function __construct(array $elements, $multiple = FALSE, $force_numeric_keys = FALSE, $default = NULL, $validators = [], $filters = [], $context = NULL, Loops $loops = NULL) {
        if(!$force_numeric_keys) {
            //make sure elements are all scalar, or array_combine will not be possible
            if(count(array_filter($elements, "is_scalar")) == count($elements)) {
                //check if the elements are consecutive numeric keys
                if(array_keys($elements) === array_keys(array_values($elements))) {
                    //if there is no duplicate set keys to values
                    if(count($elements) == count(array_unique($elements))) {
                        $elements = array_combine($elements, $elements);
                    }
                }
            }
        }
        
        if($default === NULL) {
            $default = $multiple ? [] : "";
        }

        $this->elements = $elements;
        $this->multiple = $multiple;
        parent::__construct($default, $validators, $filters, $context, $loops);
        $this->addInternalFilter(new SelectFilter($elements, $this, $loops));
    }
}