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

namespace Loops\Doctrine;

use ArrayAccess;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Loops;
use Loops\ArrayObject;
use Loops\Misc;
use Loops\Form;
use Loops\Annotations\Access\ReadOnly;
use Loops\Annotations\Access\Expose;
    
class FilteredEntityList extends EntityList {
    /**
     * @ReadOnly
     * @Expose
     */
    protected $filterform;
    
    private $prepare_callback;

    public function __construct($entity, $filter = NULL, $fields = [], $limit = 10, $alias = NULL, $order = [], $prepare_callback = NULL, $context = NULL, Loops $loops = NULL) {
        parent::__construct($entity, $limit, $alias, $order, $context, $loops);
        $classname = EntityList::getEntityClassname($entity);

        //set default filter from classname
        if($filter === NULL) {
            $parts = explode("\\", get_class($context ?: $this));
            $filter = Misc::underscore(array_pop($parts));
        }
        
        //create filterform
        $this->filterform = $this->initChild("filterform", new Form(NULL, $filter, $context, $loops));
        $this->filterform->addFromAnnotations($classname, $filter, $this, $loops);
        $this->filterform->onConfirm = function() { return FALSE; };

        EntityForm::enhanceFromEntity($this->filterform, $entity, $filter, $fields, $loops);
        
        if(is_string($prepare_callback)) {
            $prepare_callback = [ $context, $prepare_callback ];
        }
        
        if(is_callable($prepare_callback)) {
            $this->prepare_callback = $prepare_callback;
        }
    }
    
    public function action($parameter) {
        $result = parent::action($parameter);
        
        if($result === $this->filterform) {
            $this->pageAction([1]);
            return $this;
        }
        
        return $result;
    }

    protected function initialize() {
        if($this->initialized) {
            return;
        }

        $this->filterform->initFromSession();
        $this->filterform->applyFilter();
        
        if($this->prepare_callback) {
            call_user_func($this->prepare_callback, $this->builder, $this->filterform->value);
        }
        else {
            $this->prepareBuilder($this->builder, $this->filterform->value);
        }

        parent::initialize();
    }
    
    /**
     * @todo improve flow by 
     */
    public function prepareBuilder($builder, ArrayAccess $value) {
        $loops = $this->getLoops();
        
        $classname = self::getEntityClassname($this->entity, $loops);

        $metadata = $loops->getService("doctrine")->getMetadataFactory()->getMetadataFor($classname);
        
        $qb = $this->builder;
        
        foreach(array_keys($this->filterform->getFormElements()) as $name) {
            if(array_key_exists($name, $metadata->fieldMappings)) {
                if(!$value->offsetExists($name)) {
                    continue;
                }
                
                $v = $value->offsetGet($name);
                
                if($v === NULL) {
                    continue;
                }
                
                $mapping = $metadata->fieldMappings[$name];
            
                switch($mapping["type"]) {
                    case "boolean":
                    case "guid":
                    case "decimal":
                    case "float":
                    case "smallint":
                    case "integer":
                    case "bigint":  $qb->andWhere($qb->expr()->eq("{$this->alias}.$name", ":$name"));
                                    $qb->setParameter($name, $v);
                                    break;
                    case "text":
                    case "string":  $qb->andWhere($qb->expr()->like("{$this->alias}.$name", ":$name"));
                                    $qb->setParameter($name, "%$v%");
                                    break;
                    case "binary":
                    case "blob":
                    case "date":
                    case "datetime":
                    case "object":
                    default: throw new Exception("Automatic comparism of type '$mapping[type]' is not implemented yet.　Please override the 'prepareBuilder' method");
                }
            }
            
            if(array_key_exists($name, $metadata->associationMappings)) {
                if(!$value->offsetExists($name)) {
                    continue;
                }
                
                $mapping = $metadata->associationMappings[$name];
                
                $v = $value->offsetGet($name);
                
                if($mapping["type"] == ClassMetadataInfo::MANY_TO_ONE) {
                    if($v) {
                        $qb->andWhere($qb->expr()->eq("{$this->alias}.$name", ":$name"));
                        $qb->setParameter($name, $v);
                    }
                }
                else {
                    throw new Exception("Automatic comparism of mapped type '$mapping[type]' is not implemented yet.　Please override the 'prepareBuilder' method");
                }
            }
        }
    }
}