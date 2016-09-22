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

namespace Loops\Session;

use Loops;
use Loops\ArrayObject;
use Loops\Annotations\Access\ReadOnly;
use Loops\Annotations\Session\SessionVar;
use Loops\Element;
use Loops\Exception;
use Loops\Misc\InvokeTrait;
use Loops\Misc\EventTrait;
use ReflectionClass;

trait SessionTrait {
    /**
     * Set the sessionid that is passed to Phalcon\Session\Bag or
     * NULL if $loopsid should be used
     *
     * @var mixed
     * @ReadOnly
     */
    protected $sessionid   = NULL;

    /**
     * This property will be set to TRUE if the session has been initalized.
     *
     * @var boolean
     * @ReadOnly
     */
    protected $sessioninit = FALSE;

    public function initFromSession() {
        if(!($this instanceof Element)) {
            throw new Exception("Please use 'Loops\Session\SessionTrait' in a child of 'Loops\Element'.");
        }

        //check if session was already initalized
        if($this->sessioninit) {
            return FALSE;
        }

        //if the user didn't override the session id, use the loopsid
        if(!$this->sessionid) {
            $this->sessionid = $this->getLoopsId();
        }

        if(substr($this->sessionid, 0, 1) == "-") {
            throw new Exception("Failed to initialize session. The object does not have a valid session id ({$this->sessionid}). You might need to assign it to another object first.");
        }

        //load from session or create default array
        $session = $this->getLoops()->getService("session")->get($this->sessionid) ?: new ArrayObject;

        //add missing values
        foreach($this->getSessionVars() as $key) {
            if($session->offsetExists($key)) continue;

            $session->offsetSet($key, $this->$key);
        }

        //fire the initialize event which may adjust the variable
        $r = $this->fireEvent("Session\onInit", [ $session ]);

        //set session variables
        foreach($session as $key => $value) {
            $this->$key = $value;
        }

        return $this->sessioninit = TRUE;
    }

    private function getSessionVars() {
        static $cache = [];

        $classname = get_called_class();

        if(array_key_exists($classname, $cache)) {
            return $cache[$classname];
        }

        return $cache[$classname] = array_keys($this->getLoops()->getService("annotations")->get($classname)->properties->find("Session\SessionVar"));
    }

    public function saveToSession() {
        $this->initFromSession();

        //new session object
        $session = new ArrayObject;

        //initialize object from values
        foreach($this->getSessionVars() as $key) {
            $session->offsetSet($key, $this->$key);
        }

        //fire session save event - may adjust the values
        $this->fireEvent("Session\onSave", [ $session ]);

        //set in the session service
        $this->getLoops()->getService("session")->set($this->sessionid, $session);
    }

    public function clearSession() {
        $this->initFromSession();

        if(!$this->fireEvent("Session\onClear", [], TRUE)) {
            return FALSE;
        }

        $this->getLoops()->session->delete($this->sessionid);

        return TRUE;
    }
}
