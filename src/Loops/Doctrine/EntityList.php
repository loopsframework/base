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

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Loops;
use Loops\Annotations\Access\ReadOnly;
use Loops\Annotations\Access\ReadWrite;
use Loops\Annotations\Access\Expose;
use Loops\Annotations\Session\SessionVar;
use Loops\Annotations\Event\Renderer\onRender;
use Loops\Element;
use Loops\Exception;
use Loops\Session\SessionTrait;

class EntityList extends Element {
    use SessionTrait;
    
    /**
     * @var int $page The page nr which is displayed (starting from 1 = first page)
     * @ReadOnly
     * @SessionVar
     * @Expose
     */
    protected $page = 1;
    
    /**
     * @ReadWrite
     * @Expose
     */
    protected $limit;
    
    /**
     * @ReadOnly("getTotal")
     * @Expose
     */
    protected $total;
    
    /**
     * @ReadOnly("getPaginator")
     * @Expose
     */
    protected $paginator;
    
    /**
     * @ReadOnly("getLastPage")
     * @Expose
     */
    protected $last_page;
    
    /**
     * @var object $builder Doctrine Query Builder
     * @ReadOnly
     */
    protected $builder;
    
    protected $ajax_access = TRUE;
    protected $direct_access = TRUE;
    
    /**
     * @ReadOnly
     * @Expose
     */
    protected $entity;
    
    /**
     * @ReadOnly
     */
    protected $alias;
    
    protected $initialized = FALSE;
    
    public function __construct($entity, $limit = 10, $alias = NULL, $order = [], $context = NULL, Loops $loops = NULL) {
        //set alias automatically based on classname
        if(!$alias) {
            $alias = strtolower(substr($entity, 0, 1));
        }
        
        $this->alias = $alias;
        $this->entity = $entity;
        $this->limit = $limit;
        
        parent::__construct($context, $loops);
        
        $loops    = $this->getLoops();
        $doctrine = $loops->getService("doctrine");
        
        $this->builder = new QueryBuilder($doctrine->entity_manager);
        $this->builder->select($alias);
        $this->builder->from(self::getEntityClassname($entity, $loops), $alias);
        
        foreach($order as $key => $value) {
            if(is_array($value) && is_numeric($key)) {
                $this->builder->addOrderBy($this->alias.".".$value[0], $value[1]);
            }
            else {
                $this->builder->addOrderBy($this->alias.".".$key, $value);
            }
        }
    }
    
    public static function getEntityClassname($entity, Loops $loops = NULL) {
        if(is_object($entity)) {
            return get_class($entity);
        }
        
        if(!$loops) {
            $loops = Loops::getCurrentLoops();
        }
        
        return $loops->getService("doctrine")->entity_prefix.$entity;
    }
    
    public function queryEntity(array $values, &$unused = [], $strict = TRUE) {
        $loops      = $this->getLoops();
        $doctrine   = $loops->getService("doctrine");
        $classname  = self::getEntityClassname($this->entity, $loops);
        $metadata   = $doctrine->getMetadataFactory()->getMetadataFor($classname);
        $identifier = $metadata->getIdentifier();
        
        if(is_scalar($values) && count($identifier) == 1) {
            $values = [ $identifier[0] => $values ];
        }
        
        //prepare query params
        foreach($values as $key => $value) {
            if(is_numeric($key)) {
                $unused[] = $value;
            }
        }
        
        $query = [];
        foreach($identifier as $column) {
            $value = array_key_exists($column, $values) ? $values[$column] : array_shift($unused);
            
            switch($metadata->getTypeOfField($column)) {
                case 'integer': $query[$column] = (integer)$value; break;
                case 'text':    $query[$column] = (string)$value; break;
                default: throw new Exception("Not supported yet.");
            }
        }
        
        $builder = clone $this->builder;
        
        foreach($query as $key => $value) {
            $uid = uniqid("uid");
            $builder->andWhere("{$this->alias}.$key = :$uid");
            $builder->setParameter($uid, $value);
        }
        
        $query = $builder->getQuery();
        $query->setMaxResults(1);
        
        if($strict) {
            return $query->getSingleResult();
        }
        else {
            $result = $query->getResult();
            return isset($result[0]) ? $result[0] : NULL;
        }
    }
    
    public function pageAction($parameter) {
        if(count($parameter) != 1) {
            return;
        }
        
        $page = array_shift($parameter);
        
        if(!is_numeric($page)) return;
        
        if($page <= 0) return;

        $this->initFromSession();
        
        $this->page = $page;
        
        $this->saveToSession();
        
        return TRUE;
    }
    
    protected function initialize() {
        if($this->initialized) {
            return;
        }
        
        $this->initFromSession();
        
        if($this->limit > 0) {
            $this->builder->setFirstResult(($this->page-1)*$this->limit);
            $this->builder->setMaxResults($this->limit);
        }
        
        $this->paginator = new Paginator($this->builder);
        $this->total     = $this->paginator->count();
        $this->last_page = $this->limit > 0 ? (integer)ceil($this->total / $this->limit) : 1;
        
        $this->initialized = TRUE;
    }
    
    public function getPaginator() {
        $this->initialize();
        return $this->paginator;
    }
    
    public function getLastPage() {
        $this->initialize();
        return $this->last_page;
    }
    
    public function getTotal() {
        $this->initialize();
        return $this->total;
    }
}