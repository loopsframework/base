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

use ArrayAccess;

/**
 * The ElementInterface
 *
 * An object which implements the ElementInterface will become compatible with the Loops request processing logic.
 * Elements (Objects that implements the ElementInterface) can be arranged in a hierarchical tree which define the available routes of the web application.
 * They have an unique ID and can be asked to accept a request (via the action method).
 * For more details refer the action method documentation.
 *
 * A route of an element can be retrieved by Loops\WebCore::getPagePath
 */
interface ElementInterface extends ArrayAccess {
    /**
     * Should return a value if this element wants to accept a request based on passed parameter.
     * Check the documentation of the Loops\WebCore class for more details.
     *
     * If the return value is
     *    boolean FALSE or NULL: Do not accept the request
     *    boolean TRUE: Accept this request
     *    object: The request was accepted by this element
     *    integer greater than 0: Generate an error page with status code of the integer value
     *    integer 0 or less: Accept the request and use an empty response body
     *    string: Accept the request and use the string value as the response body
     *    other: undefined behaviour
     *
     * @param array<string> $parameter The action parameters (=accessed URL) that is requested.
     * @return mixed A value that defines if the request should be accepted.
     */
    public function action($parameter);

    /**
     * Returns the offset of the object from its parent.
     *
     * This function must return the offset with which this object can be accessed from its parent.
     *
     * <code>
     *     // the following must result to TRUE
     *     !$this->getParent() || $this->getParent()->offsetGet($this->getName()) === $this
     * </code>
     *
     * The implementation must enforce this behaviour.
     *
     * @todo Think about changing this method to getOffsetOf(ElementInterface $child) - that would more likely be a correct representation of the hierarchical model
     * @return string The name of this element.
     */
    public function getName();
    
    /**
     * Return the parent element in the hierarchy.
     *
     * @return object|NULL The parent object or NULL if this element does not have a parent.
     */
    public function getParent();
    
    /**
     * Return an unique id based on the objects position in the hierarchy
     *
     * This function should return an unique id that is based on the position in the hierarchy and should be consistent over multiple requests.
     *
     * @return string The unique id.
     */
    public function getLoopsId();
    
    /**
     * Returns the page path of an object
     *
     * The page path of an object is the URL that should point to an element. That means that the action method
     * will be called without parameters if the returned value is accessed.
     * How the page path is build depends on the request handling of the action method.
     * Each element should be able to be addressed by a pagepath, either if the object accepts the request or not.
     * The page path does not include the domain name or preceeding slashes. It should be relative to the top level of the
     * Loops application.
     *
     * @return string The URL with which this object can be addressed
     */
    public function getPagePath();
    
    /**
     * Return if the object is a page or normal element.
     *
     * An element must be a page in order to be able to be recognized by Loops\WebCore
     */
    public static function isPage();
}