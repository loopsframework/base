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

namespace Loops;

use IteratorAggregate;
use Serializable;
use Loops;
use Loops\Renderer\CacheInterface;
use Loops\Misc\AccessTrait;
use Loops\Annotations\Access\ReadOnly;
use Loops\Annotations\Access\ReadWrite;
use Loops\Annotations\Access\Expose;

/**
 * An element that can be structured in hierarical trees
 *
 * Instances of the Loops\Element class largely define how Loops processes requests. The Loops\ElementInterface is implemented
 * and parent/name values are automatically updated.
 * The action method of the Loops\Element class implements the (recommended) way of how Loops processes request urls that have
 * been split into parameter.
 *
 * Loops\Element inherits from Loops\Object and implements all its magic Loops behaviour. It also implements the
 * Loops\Renderer\CacheInterface. The behaviour of this interface is quickly configurable by settings default properties or
 * overriding methods.
 *
 */
abstract class Element extends Object implements ElementInterface, CacheInterface {
    /**
     * @var string|FALSE Used by the action method to determine a default offset where a request may be forwarded to.
     *
     * @ReadOnly
     */
    protected $delegate_action = FALSE;

    /**
     * @var bool Used by the action method to specify if the element accepts the request on default.
     *
     * See method action for details.
     */
    protected $direct_access = FALSE;

    /**
     * @var bool Used by the action method to specify if the element accepts the request on default during an ajax request.
     *
     * See method action for details.
     */
    protected $ajax_access = FALSE;

    /**
     * @var integer The renderer cache lifetime of this object in seconds.
     * @ReadWrite
     *
     * A negative value disabled the renderer cache.
     * 0 defines that the cache never expires.
     */
    protected $cache_lifetime = -1;

    /**
     * @var string Magic access to ->getLoopsId()
     * @Expose
     * @ReadOnly("getLoopsId")
     */
    protected $loopsid;

    /**
     * @var string Magic access to ->getPagePath()
     * @Expose
     * @ReadOnly("getPagePath")
     */
    protected $pagepath;

    /**
     * @var mixed The creation context (see constructor)
     * @ReadOnly
     */
    protected $context;

    /**
     * Can be returned from action methods for convinience
     */
    const NO_OUTPUT = -1;

    private $__name           = NULL;
    private $__parent         = NULL;

    /**
     * The contructror
     *
     * A creation context can be passed to the constructor. It can be any value but should
     * be set to the object which is responsible of creating this instance. This is done
     * automatically when creating elements via loops annotations.
     * Usually this value will be the same as the parent object of this element.
     *
     * @param mixed $context The creation context
     * @param Loops\Context $loops The context that is used to resolve services.
     *
     * The content will default to the last Loops context.
     */
    public function __construct($context = NULL, Loops $loops = NULL) {
        $this->context = $context;
        parent::__construct($loops);
    }

    /**
     * Generate the Loops id of this object.
     *
     * If the object has no parent use its hash as the Loops id.
     * Otherwise add its name to the parents Loops id separated with a dash
     *
     * @param string|NULL $refkey Defines the offset of a child which is requesting the Loops id
     * @return string The Loops id
     */
    protected function __getLoopsId($refkey = NULL) {
        if($this->__parent) {
            return $this->__parent->__getLoopsId($this->__name)."-".$this->__name;
        }

        return spl_object_hash($this);
    }

    /**
     * Returns if the object is cacheable based on the cache_lifetime property.
     *
     * @return bool TRUE if the renderer chache should be used for this object.
     */
    public function isCacheable() {
        return $this->cache_lifetime >= 0;
    }

    /**
     * Return the cacheid
     *
     * By default, the elements Loops ID is going to be used.
     * This method should be overridden if the appearance changes based on other factors.
     *
     * @return string The Cache ID
     */
    public function getCacheId() {
        return $this->getLoopsId();
    }

    /**
     * Returns the cache lifetime in seconds (=property cache_lifetime)
     *
     * @return integer The cache lifetime in seconds
     */
    public function getCacheLifetime() {
        return $this->cache_lifetime;
    }

    /**
     * {@inheritdoc}
     */
    public function getLoopsId() {
        return $this->__getLoopsId();
    }

    /**
     * Adds an element into the hierarchy.
     *
     * This function is a simple wrapper for offsetSet but introduces type checking.
     *
     * @param string $name The name of the child element
     * @param Loops\Element $child The child element
     */
    public function addChild($name, Element $child) {
        $this->offsetSet($name, $child);
    }

    /**
     * Internal use, an Element instances __parent and __name property are automatically updated
     *
     * @param string $name The name of the property where the Loops\Element is stored
     * @param mixed $child The value that is going to be checked and adjusted if it is a Loops\Element
     * @param bool $detacht Detach the element from its old parent if exists.
     * @return mixed The passed $child value
     */
    protected function initChild($name, $child, $detach = FALSE) {
        if($child instanceof Element) {
            if($detach && $child->__parent && $child->__parent !== $this) {
                $child->detach();
            }

            if(!$child->__parent) {
                $child->__parent = $this;
                $child->__name = $name;
            }
        }
        return $child;
    }

    /**
     * Automatically initializes child elements in case they were not initialized yet
     *
     * @param string $offset The element offset
     */
    public function offsetGet($offset) {
        $value = parent::offsetGet($offset);
        return $offset === "context" ? $value : $this->initChild($offset, $value);
    }

    /**
     * Automatically initailizes child elements in case they were not initialized yet
     *
     * If a element was already set on another element object, it will be detached.
     *
     * @param string $offset The offset
     * @param mixed $value The value to be set at offset
     */
    public function offsetSet($offset, $value) {
        parent::offsetSet($offset, $value);
        $this->initChild($offset, $value, TRUE);
    }

    /**
     * Automatically detaches child elements if they belong to this element
     *
     * @param string The offset
     */
    public function offsetUnset($offset) {
        $detach = NULL;

        if(parent::offsetExists($offset)) {
            $child = parent::offsetGet($offset);
            if($child instanceof Element && $child->__parent === $this) {
                $detach = $child;
            }
        }

        parent::offsetUnset($offset);

        if($detach) {
            $detach->detach();
        }
    }

    /**
     * A class internal way of iterating over class properties.
     *
     * Wrapper to the AccessTrait getGenerator method.
     *
     * @param bool $include_readonly Include values that have been marked with the {\@}ReadOnly or {\@}ReadWrite annotations.
     * @param bool $include_protected Also include protected values without annotations.
     * @param array Only include values with keys that are specified in this array.
     * @param array Exclude values with keys that are specified in this array.
     * @return Generator A generator that traverses over the requested values of this object.
     */
    protected function getGenerator($include_readonly = FALSE, $include_protected = FALSE, $include = FALSE, $exclude = FALSE) {
        foreach(parent::getGenerator($include_readonly, $include_protected, $include, $exclude) as $key => $value) {
            yield $key => ($key === "context") ? $value : $this->initChild($key, $value);
        }
    }

    /**
     * Default action processing
     *
     * The default behaviour of an element is to not accept a request.
     * An element only accepts requests on default, when no parameter were passed and direct_access has been set to TRUE.
     * In an ajax call, it is also possible to set ajax_access to TRUE for accepting the request. The service request will
     * be checked if this currently is an ajax call.
     * The action method will return itself (rather than TRUE) when accepting a request. This makes it possible to determine
     * which (sub) element actually accepted the request.
     *
     * If parameters are given, the following logic can be used to determine if a request should be accepted:
     * 1. Take the first parameter and check for a method named "{param}Action", pass all parameters to it and use the resulting value.
     * If such a method does not exist or the value is NULL or FALSE do not accept the request, continue.
     * 2. Take the first parameter and check if there is another Loops\Element instance at the offset defined by the parameter (->offsetExists($param) & ->offsetGet($param))
     * If such object exists execute that objects action, pass the rest of the parameter and use its return value, continue if it was NULL or FALSE.
     * 3. Check if the element defines a property delegate_action and, prepend it to the action parameters and apply step 2.
     *
     * @param array $parameter The action parameter.
     * @return mixed The processed value
     */
    public function action($parameter) {
        $result = FALSE;

        if($parameter) {
            $name = $parameter[0];

            $method_name = $name."Action";

            if(method_exists($this, $method_name)) {
                $result = $this->$method_name(array_slice($parameter, 1));
            }

            if(in_array($result, [FALSE, NULL], TRUE)) {
                if($parameter && $this->offsetExists($name)) {
                    $child = $this->offsetGet($name);
                    if($child instanceof ElementInterface) {
                        $result = $child->action(array_slice($parameter, 1));
                    }
                }
            }
        }

        if(in_array($result, [FALSE, NULL], TRUE)) {
            if($this->delegate_action) {
                if($this->offsetExists($this->delegate_action)) {
                    $child = $this->offsetGet($this->delegate_action);
                    if($child instanceof ElementInterface) {
                        $result = $child->action($parameter);
                    }
                }
            }
        }

        if(in_array($result, [FALSE, NULL], TRUE)) {
            if(!$parameter) {
                if($this->direct_access) {
                    $result = TRUE;
                }
                else {
                    $loops = $this->getLoops();

                    if($loops->hasService("request") && $loops->getService("request")->isAjax() && $this->ajax_access) {
                        $result = TRUE;
                    }
                }
            }
        }

        if($result === TRUE) {
            $result = $this;
        }

        return $result;
    }

    /**
     * Reset the internal caching mechanism of the parent element and name.
     * This may be needed if you want to assign the element on a different object.
     *
     * @return Loops\Element Returns $this for method chaining.
     */
    public function detach() {
        if($this->__parent) {
            $this->__parent->offsetUnset($this->__name);
        }

        $this->__parent = NULL;
        $this->__name = NULL;
        return $this;
    }

    /**
     * Returns the parent elemenet
     *
     * @return Loops\Element The parent object or FALSE if the element has no parent.
     */
    public function getParent() {
        return $this->__parent ?: FALSE;
    }

    /**
     * Returns the offset name with which this object can be accessed from its parent object.
     *
     * @return string|FALSE Returns FALSE if the element has no parent.
     */
    public function getName() {
        return $this->__parent ? $this->__name : FALSE;
    }

    /**
     * Returns the page path of this object. (See documentation of Loops\ElementInterface for details)
     *
     * If the element has no parent or is not present in a hierachy with a page element at the top, no
     * page path can be generated and FALSE is returned.
     *
     * @return string
     */
    public function getPagePath() {
        if(!$this->__parent) {
            return FALSE;
        }

        $pagepath = $this->__parent->getPagePath();

        if($pagepath === FALSE) {
            return FALSE;
        }

        if($this->__parent->delegate_action == $this->__name) {
            return $pagepath;
        }

        return ltrim(rtrim($pagepath, "/")."/".$this->__name, "/");
    }

    /**
     * Returns FALSE on default
     *
     * @return false
     */
    public static function isPage() {
        return FALSE;
    }
}
