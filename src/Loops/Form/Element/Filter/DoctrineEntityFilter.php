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

namespace Loops\Form\Element\Filter;

use Loops;
use Loops\Form\Element\Filter;
use Loops\Doctrine\EntityList;

class DoctrineEntityFilter extends Filter {
    private $entity_list;
    
    public function __construct(EntityList $entity_list, Loops $loops = NULL) {
        parent::__construct($loops);
        
        $this->entity_list = $entity_list;
    }
    
    public function filter($value) {
        if(is_object($value)) {
            $doctrine = $this->getLoops()->getService("doctrine");
            $value = $doctrine->merge($value);
            $doctrine->refresh($value);
            return $value;
        }
        else {
            return $this->entity_list->queryEntity((array)$value);
        }
    }
}