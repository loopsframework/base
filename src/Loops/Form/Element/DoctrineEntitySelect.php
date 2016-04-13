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
use Loops\Annotations\Access\ReadOnly;
use Loops\Annotations\Access\ReadWrite;
use Loops\Annotations\Access\Expose;
use Loops\Annotations\Form\Element as ElementAnnotation;
use Loops\Exception;
use Loops\Form\Element;
use Loops\Form\Element\Filter\DoctrineEntityFilter;
use Loops\Doctrine\EntityList;
use Loops\Doctrine\FilteredEntityList;

class DoctrineEntitySelect extends Element {
    /**
     * @ReadWrite
     */
    protected $nullable;
    
    /**
     * @ReadOnly
     * @Expose
     */
    protected $entitylist;
    
    public function __construct($entitylist, $default = NULL, $nullable = TRUE, $validators = [], $filters = [], $context = NULL, Loops $loops = NULL) {
        parent::__construct($default, $validators, $filters, $context, $loops);

        if(is_string($entitylist)) {
            $entitylist = new FilteredEntityList($entitylist, NULL, [], 10, NULL, [], NULL, $this);
        }
        
        if(!($entitylist instanceof EntityList)) {
            throw new Exception("Passed EntityList must be of type 'Loops\Doctrine\EntityList'");
        }
        
        $this->nullable = $nullable;
        $this->entitylist = $entitylist;
        
        $this->addInternalFilter(new DoctrineEntityFilter($entitylist, $loops));
    }
}