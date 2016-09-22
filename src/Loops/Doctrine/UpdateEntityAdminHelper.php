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
use Loops\Element;
use Loops\Renderer\CustomizedRenderInterface;
use Loops\Annotations\Access\ReadOnly;

class UpdateEntityAdminHelper extends Element implements CustomizedRenderInterface {
    private $parameter;
    private $missing;
    private $elements = [];

    /**
     * @ReadOnly
     */
    protected $delegate;

    public function __construct(EntityAdmin $context, $parameter = [], Loops $loops = NULL) {
        parent::__construct($context, $loops);

        $loops      = $this->getLoops();
        $doctrine   = $loops->getService("doctrine");
        $classname  = $doctrine->entity_prefix.$context->entity;
        $metadata   = $doctrine->getMetadataFactory()->getMetadataFor($classname);
        $identifier = $metadata->getIdentifier();

        $this->parameter = $parameter;
        $this->missing = count($identifier) - count($parameter) - 1 > 0;
    }

    public function setDelegateByParameter($parameter) {
        if(!$parameter) {
            return FALSE;
        }

        $element = $this;

        while($element->offsetExists($parameter[0])) {
            $element = $element->offsetGet(array_shift($parameter));
            if($element instanceof UpdateEntityForm) {
                return $this->delegate = $element;
            }
        }

        return FALSE;
    }

    public function delegateRender() {
        return $this->delegate;
    }

    public function getTemplateName() {
    }

    public function modifyAppearances(&$appearances, &$forced_appearances) {
    }

    public function offsetExists($offset) {
        if($this->missing) {
            return TRUE;
        }

        if(!array_key_exists($offset, $this->elements)) {
            $parameter  = array_merge($this->parameter, [$offset]);

            if($entity = $this->context->offsetGet("list")->queryEntity($parameter, $unused, FALSE)) {
                $this->elements[$offset] = new UpdateEntityForm($entity, ["", "update_entity"], [], $this, $this->getLoops());
            }
            else {
                $this->elements[$offset] = NULL;
            }
        }

        if($this->elements[$offset]) {
            return TRUE;
        }

        return parent::offsetExists($offset);
    }

    public function offsetGet($offset) {
        if($this->missing) {
            $intermediate_helper = new UpdateEntityAdminHelper($this->context, array_merge($this->parameter, [$offset]));
            return $this->initChild($offset, $intermediate_helper);
        }

        if($this->offsetExists($offset) && array_key_exists($offset, $this->elements)) {
            return $this->initChild($offset, $this->elements[$offset]);
        }

        return parent::offsetGet($offset);
    }
}
