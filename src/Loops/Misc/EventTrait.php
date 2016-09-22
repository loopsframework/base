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

namespace Loops\Misc;

use ReflectionClass;

use Loops;
use Loops\Object;
use Loops\Exception;
use Loops\Annotations\Misc\Event;
use Loops\Annotations\Access\Sleep;

/**
 * A trait which implements event handling for objects.
 *
 * Methods or properties can listen to events if they are marked with the Listen (Loops\Annotations\Listen) annotation.
 * A property must be a valid callable in order to be executed if the event is fired.
 * It is also possible to manually register event callbacks.
 *
 * <code>
 *     use Loops\Annotations\Event;
 *     use Loops\Misc\EventTrait;
 *
 *     class Dog {
 *         trait EventTrait;
 *
 *         /**
 *          * ("\@")Listen("onFeed")
 *          {@*}
 *         public $feed_callback;
 *
 *         /**
 *          * ("\@")Listen("onFeed")
 *          {@*}
 *         public function bark() {
 *             echo "Woof!";
 *         }
 *     }
 *
 *     $bello = new Dog;
 *     $bello->feed_callback = function() { echo "Wooof!"; };
 *     $bello->addListener("onFeed", function() { echo "Woooof!"; });
 *     $bello->fireEvent("onFeed");
 * </code>
 *
 * It is also possible to notify other objects of events by using the bindEventObject method.
 *
 * <code>
 *     use Loops\Annotations\Event;
 *     use Loops\Misc\EventTrait;
 *
 *     class House {
 *         trait EventTrait;
 *     }
 *
 *     class Park {
 *         trait EventTrait;
 *     }
 *
 *     class Dog {
 *         trait EventTrait;
 *
 *         /**
 *          * ("\@")Listen("onIntruderArrived")
 *          {@*}
 *         public function bark() {
 *             echo "Woof!";
 *         }
 *     }
 *
 *     $bello = new Dog;
 *
 *     $villa = new House;
 *     $villa->bindEventObject($bello);
 *     $villa->fireEvent("onIntruderArrived");
 *
 *     $central_park = new Park;
 *     $central_park->bindEventObject($bello);
 *     $central_park->fireEvent("onIntruderArrived");
 * </code>
 *
 * @Sleep(exclude={"registered_events","annotated_events","bound_event_objects","bound_self","event_cache_state"})
 */
trait EventTrait {
    private $registered_events = [];
    private $annotated_events = [];
    private $bound_event_objects = [];
    private $bound_self = FALSE;
    private $event_cache_state = [];

    /**
     * Registers all event listener of an object to this object.
     *
     * After an object is bound, all listeners of that object will also be called if an event is fired (on this object).
     *
     * @param object $object The target object from which the listeners will be registered.
     * @param bool|string[] Only register the listeners that are passed via an array. Set to FALSE to register all listeners.
     */
    public function bindEventObject($object, $filter = FALSE) {
        $this->bound_event_objects[] = [ $filter === FALSE ? FALSE : (array)$filter, $object ];
        $this->event_cache_state = [];
    }

    /**
     * Manually adds a listener
     *
     * @param string $name The name of the event.
     * @param callable $callback The callable that will be called if the event is fired.
     */
    public function addListener($name, callable $callback) {
        if(!array_key_exists($name, $this->registered_events)) {
            $this->registered_events[$name] = [];
        }

        $this->registered_events[$name][] = $callback;
    }

    /**
     * Fires an event
     *
     * All listeners will be executed and their return values are collected.
     * Simple aggregate processing can be done on the return values if needed.
     * By default, if no callback **specifically** returns FALSE and the aggregate is requested, the return value will evaluate to TRUE.
     *
     * @param string $name The name of the event.
     * @param mixed[] $arguments The arguments that are passed to the listeners.
     * @param bool $boolean_aggregate If set to TRUE, the returned values will be converted to boolean and returned as an aggregate (boolean AND operator on all values).
     * @param bool $null_is_true If a callback does not return any value, assume that TRUE was returned.
     * @param bool $empty_is_true Return TRUE if no callback was executed.
     * @return bool|mixed[] All return values of the registered listeners in an array or an boolean aggregate of these values.
     */
    public function fireEvent($name, array $arguments = [], $boolean_aggregate = FALSE, $null_is_true = TRUE, $empty_is_true = TRUE) {
        $this->registerAnnotatedEvents($name);

        $events = [];

        foreach($this->annotated_events[$name] as $event) {
            list($is_method, $object, $key) = $event;

            //if event is stored in a property
            if($is_method) {
                $callable = [ $object, $key ];
            }
            else {
                if($object instanceof ArrayAccess) {
                    $callable = $object->offsetGet($key);
                }
                else {
                    $callable = $object->$key;
                }
            }

            //allow NULL properties
            if($callable === NULL) {
                continue;
            }

            if(!is_callable($callable)) {
                throw new Exception("Can not fire listener of event in property '$property' of class '".get_class($this)."'.");
            }

            $events[] = $callable;
        }

        if(array_key_exists($name, $this->registered_events)) {
            $events = array_merge($events, $this->registered_events[$name]);
        }

        if(!$events && $boolean_aggregate) {
            return $empty_is_true;
        }

        $result = [];

        foreach($events as $event) {
            $result[] = call_user_func_array($event, $arguments);
        }

        if(!$boolean_aggregate) {
            return $result;
        }

        foreach($result as $value) {
            if(is_callable($boolean_aggregate)) {
                $value = $boolean_aggregate($value);
            }

            if($value || ($null_is_true && $value === NULL)) {
                continue;
            }

            return FALSE;
        }

        return TRUE;
    }

    private function registerAnnotatedEvents($name) {
        if(in_array($name, $this->event_cache_state)) {
            return;
        }

        if(!$this->bound_self) {
            $this->bindEventObject($this);
            $this->bound_self = TRUE;
        }

        $this->annotated_events[$name] = [];

        foreach($this->bound_event_objects as $pair) {
            list($filter, $object) = $pair;

            if($filter !== FALSE && !in_array($name, $filter)) {
                continue;
            }

            $loops = $this instanceof Object ? $this->getLoops() : Loops::getCurrentLoops();

            $annotations = $loops->getService("annotations")->get(get_class($object));

            foreach($annotations->methods->find("Listen") as $key => $annotationarray) {
                foreach($annotationarray as $annotation) {
                    if($annotation->value != $name) continue;
                    $this->annotated_events[$name][] = [ TRUE, $object, $key ];
                }
            }

            foreach($annotations->properties->find("Listen") as $key => $annotationarray) {
                foreach($annotationarray as $annotation) {
                    if($annotation->value != $name) continue;
                    $this->annotated_events[$name][] = [ FALSE, $object, $key ];
                }
            }
        }

        $this->event_cache_state[] = $name;
    }
}
