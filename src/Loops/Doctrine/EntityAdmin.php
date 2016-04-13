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

use Loops;
use Loops\Exception;
use Loops\Element;
use Loops\Form;
use Loops\Misc;
use Loops\Annotations\Access\ReadOnly;
use Loops\Annotations\Access\ReadWrite;
use Loops\Annotations\Access\Expose;
use Loops\Annotations\Object;

class EntityAdmin extends Element {
    /**
     * @ReadOnly
     * @Expose
     */
    protected $persist = TRUE;
    
    /**
     * @ReadOnly
     * @Expose
     */
    protected $update  = TRUE;
    
    /**
     * @ReadOnly
     * @Expose
     */
    protected $delete  = TRUE;
    
    /**
     * @ReadOnly
     * @Expose
     */
    protected $entity;
    
    /**
     * @ReadOnly("getList")
     */
    protected $list;
    
    /**
     * @Object("getAddForm",type="CALLBACK")
     * @ReadOnly
     */
    protected $add;
    
    /**
     * @Object("getEditForm",type="CALLBACK")
     * @ReadOnly
     */
    protected $edit;
    
    /**
     * @ReadOnly
     * @Expose
     */
    protected $mode = "list";
    
    protected $delegate_action = "list";
    
    //store parameters for other element creation
    private $list_prepare_callback;
    private $persist_filter;
    private $persist_fields;
    private $update_filter;
    private $update_fields;
    private $list_alias;
    private $list_order;
    private $list_limit;
    private $list_filter;
    private $list_fields;

    public function __construct($entity, $persist = TRUE, $update = TRUE, $delete = TRUE, $list = TRUE, $list_filter = [ "filtered_entity_list", "entity_admin" ], $list_fields = [], $list_limit = 10, $list_alias = NULL, $list_order = [], $list_prepare_callback = NULL, $persist_filter = ["", "persist_entity"], $persist_fields = [], $update_filter = ["", "update_entity"], $update_fields = [], $context = NULL, Loops $loops = NULL) {
        parent::__construct($context, $loops);

        $this->entity = $entity;
        
        $this->persist        = $persist;
        $this->persist_filter = $persist_filter;
        $this->persist_fields = $persist_fields;
        
        $this->update        = $update;
        $this->update_filter = $update_filter;
        $this->update_fields = $update_fields;
        
        $this->delete = $delete;
        
        $this->list = $list;
        $this->list_alias  = $list_alias;
        $this->list_order  = $list_order;
        $this->list_limit  = $list_limit;
        $this->list_filter = $list_filter;
        $this->list_fields = $list_fields;
        $this->list_prepare_callback = is_string($list_prepare_callback) ? [ $context, $list_prepare_callback] : $list_prepare_callback;
    }
    
    public function deleteAction($parameter) {
        if(!$this->delete) {
            return FALSE;
        }
        
        $loops      = $this->getLoops();
        $request    = $loops->getService("request");
        
        if(!$request->isPost()) {
            return FALSE;
        }
        
        $entity = $this->offsetGet("list")->queryEntity($parameter, $unused);

        if($unused) {
            return FALSE;
        }
        
        $doctrine = $loops->getService("doctrine");
        $doctrine->remove($entity);
        $doctrine->flush();
        
        return Misc::redirect($this, 302, $loops);
    }

    public function getList() {
        if(is_object($this->list)) {
            return $this->list;
        }
        
        if(is_string($this->list)) {
            $arguments["entity"]            = $this->entity;
            $arguments["limit"]             = $this->list_limit;
            $arguments["alias"]             = $this->list_alias;
            $arguments["order"]             = $this->list_order;
            $arguments["filter"]            = $this->list_filter;
            $arguments["fields"]            = $this->list_fields;
            $arguments["prepare_callback"]  = $this->list_prepare_callback;
            $arguments["context"]           = $this;
            $arguments["loops"]             = $this->getLoops();
    
            return $this->list = Misc::reflectionInstance($this->list, $arguments);
        }
        
        return $this->list = new FilteredEntityList($this->entity, $this->list_filter, $this->list_fields, $this->list_limit, $this->list_alias, $this->list_order, $this->list_prepare_callback, $this, $this->getLoops());
    }
    
    public function getAddForm() {
        if(is_string($this->persist)) {
            $arguments["entity"]            = $this->entity;
            $arguments["filter"]            = $this->persist_filter;
            $arguments["fields"]            = $this->persist_fields;
            $arguments["context"]           = $this;
            $arguments["loops"]             = $this->getLoops();

            return Misc::reflectionInstance($this->persist, $arguments);
        }
        
        if($this->persist) {
            if($this->persist instanceof Form) {
                return $this->persist;
            }
            
            return new PersistEntityForm($this->entity, $this->persist_filter, $this->persist_fields, $this, $this->getLoops());
        }
    }
    
    public function getEditForm() {
        if($this->update) {
            if($this->update instanceof Form) {
                return $this->update;
            }
            
            return new UpdateEntityAdminHelper($this);
        }
    }
    
    public function addAction($parameter) {
        if($this->offsetGet("add")) {
            $this->mode = "add";
        }
    }
    
    public function editAction($parameter) {        
        if(($edit = $this->offsetGet("edit")) && $edit->setDelegateByParameter($parameter)) {
            $this->mode = "edit";
        }
    }

    public function action($parameter) {
        $result = parent::action($parameter);
        
        if($this->mode == "edit" && $this->edit->delegateRender() === $result) return $this;
        if($this->mode == "add"  && $this->persist === $result) return $this;
        if($this->mode == "list" && $this->list === $result) return $this;
        return $result;
    }
}
