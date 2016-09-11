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

use ArrayIterator;
use ArrayAccess;
use ReflectionClass;
use Loops;
use Loops\Annotations\Access\ReadOnly;
use Loops\Annotations\Access\ReadWrite;
use Loops\Annotations\Access\Expose;
use Loops\Annotations\Listen;
use Loops\Annotations\Session\SessionVar;
use Loops\Exception;
use Loops\Misc\InvokeTrait;
use Loops\Misc\EventTrait;
use Loops\Messages\MessageList;
use Loops\Messages\Message;
use Loops\Session\SessionTrait;
use Loops\Form\Element\SubForm;
use Loops\Form\Value;

/**
 * An input form that can filter/validate/process user data.
 *
 * It is used to filter, validate and process user input.
 * The form is defined by form elements that are added to the form.
 * Form elements take care about filtering (adjusting) the user input and produce errors on invalid input.
 *
 * 1. Form setup
 *
 * The form class is concepted to initialize itself automatically based on a passed object (entity)
 * or by inheritance.
 *
 * a) by object (entity)
 *
 * Annotations from the passed object (entity) class definition will be used.
 * Please note that the class must implement the ArrayAccess interface.
 *
 * <code>
 *     class Entity extends ArrayObject {
 *
 *     }
 *
 *
 * </code>
 *
 * b) by inheritance
 * c) mixed
 * d) manually
 *
 * Form elements can also be added by simply assigning them to the form.
 *
 * For a more formal explanation please refer the the constructor documentation.
 *
 *
 *
 * <code>
 * </code>
 *
 * 2. Using the form
 *
 * tba
 *
 * 3. The form on Loops pages
 *
 * tba
 *
 * 4. Events
 *
 * tba
 */
class Form extends Element {
    use SessionTrait {
        SessionTrait::initFromSession as SessionTraitInitFromSession;
    }

    protected $ajax_access = TRUE;
    protected $direct_access = TRUE;

    /**
     * @var bool $noconfirm If set to true, the confirm page has to be skipped on url based access. Directly access the submit page with the POST data in this case.
     *
     * @ReadWrite
     * @Expose
     */
    protected $no_confirm   = FALSE;

    /**
     * @var bool $confirmed States if the form is in confirmed state.
     *
     * @ReadOnly
     * @Expose
     * @SessionVar
     */
    protected $confirmed   = FALSE;

    /**
     * @var bool $confirmed States if the form is in submitted state.
     *
     * @ReadOnly
     * @Expose
     */
    protected $submitted   = FALSE;

    /**
     * @var Loops\Messages\MessageList $messages Holds (error) messages that should be displayed with the form.
     *
     * @ReadOnly
     * @Expose
     */
    protected $messages;

    /**
     * @var bool Should be set to TRUE if this form is a part of another form.
     *
     * This value is a hint for form elements to determine their element name.
     *
     * @ReadWrite
     */
    protected $weak = FALSE;

    /**
     * @var callable A callbackobject that will be executed on form validation.
     *
     * @Listen("Form\onValidate")
     * @ReadWrite
     */
    protected $onValidate;

    /**
     * @var callable A callbackobject that will be executed on form confirmation.
     *
     * @Listen("Form\onConfirm")
     * @ReadWrite
     */
    protected $onConfirm;

    /**
     * @var callable A callbackobject that will be executed on form submission.
     *
     * @Listen("Form\onSubmit")
     * @ReadWrite
     */
    protected $onSubmit;

    /**
     * @var array of Loops\Form\Element A list of all elements of the form.
     *
     * @Expose
     * @ReadOnly("getFormElements")
     */
    protected $elements;

    /**
     * @var ArrayAccess The current value of the form.
     *
     * @Expose
     * @SessionVar
     * @ReadOnly("getValue")
     */
    protected $value;

    /**
     * @Listen("Form\onValidate")
     */
    protected function onValidate($value) {
        return TRUE;
    }

    /**
     * @Listen("Form\onConfirm")
     */
    protected function onConfirm($value) {
        return TRUE;
    }

    /**
     * @Listen("Form\onSubmit")
     */
    protected function onSubmit($value) {
        return TRUE;
    }

    /**
     * Construct a form object
     *
     * Form elements will be added to the form based on annotations.
     * Loops will look for annotations of the type 'Loops\Annotations\Form\Element',
     * 'Loops\Annotations\Form\Validator' and 'Loops\Annotations\Form\Filter' on properties.
     *
     * Annotations are collected from two places.
     *
     * 1. The passed entity
     *     If the passed default value (entity) is an object, the properties of its class definition will be inspected for annotations.
     *     For every annotation found, a form element is added.
     *     If the object (entity) has listeners to form events, they will also be fired on the object in case an event is fired for this form.
     * 2. The form itself
     *     Properties of the class instances defining class will also be inspected for annotations.
     *     This is particulary useful when extending from this class.
     *     Otherwise no annotations will be found since this class itself does not define any.
     *
     * Annotations from all parent classes will be collected.
     * For further information, see the documentation of method addFromAnnotations.
     *
     * @param ArrayAccess $value The default value/entity. If set to NULL, an empty Loops\ArrayObject will be created.
     * @param array|string Only use annotations marked with the specified filter(s) to generate form elements.
     * @param Loops The loops context to which this object belongs to.
     */
    public function __construct(ArrayAccess $value = NULL, $filter = "", $context = NULL, Loops $loops = NULL) {
        parent::__construct($context, $loops);

        $this->messages = new MessageList(Message::ERROR);

        $this->value = $value ?: new ArrayObject;

        if($value) {
            $this->addFromAnnotations($value, $filter);
        }

        $this->addFromAnnotations($this, $filter);
    }

    /**
     * Adds form elements based on annotations.
     *
     * All properties of the passed classname are inspected for annotations of the type
     * "Loops\Annotations\Form\Element" (or child classes of the type).
     * These annotations extends from "Loops\Annotations\Object" and thus can be used to create a new
     * instances of form elements that are assigned to this form.
     * The form element will be assigned by the property name where the annotations was found.
     *
     * The following example will assign an element of type "Loops\Form\Element\Number" to the form.
     * <code>
     *
     * use Loops\Annotations\Form\Element;
     * use Loops\Form;
     *
     * class ExampleClass {
     *     /**
     *      * \@Element("Number")
     *      {@*}
     *     public $test;
     * }
     *
     * $form = new Form;
     * $form->addFromAnnotations("ExampleClass");
     *
     * </code>
     *
     * Due to the nature of how the "Loops\Annotations\Object" instanciation is working, it is possible
     * to define arguments for the constructor in the annotation.
     *
     * The $filter argument can be used to select specific annotations.
     * Annotations also may have a filter property and are only assigned to the form if this filter property matches with the
     * passed $filter argument.
     * By specifying the filter property it is possible to define groups of elements.
     *
     * On default, the annotations filter property is "" (empty string) as is the default value for the $filter argument.
     * It is important to understand that the filter "" (empty string) is considered as an isolated group by its own and is
     * used on default.
     *
     * Properties may also have more than one "Loops\Annotations\Form\Element" annotation.
     *
     * Example of filter groups:
     * <code>
     *
     * use Loops\Annotations\Form\Element;
     * use Loops\Form;
     *
     * class ExampleClass2 {
     *     /**
     *      * \@Element("Number",filter="group1")
     *      {@*}
     *     public $test;
     *
     *     /**
     *      * \@Element("Number",filter="group1")
     *      {@*}
     *     public $test2;
     *
     *     /**
     *      * \@Element("Number",filter="group2")
     *      {@*}
     *     public $test3;
     *
     *     /**
     *      * \@Element("Number")
     *      * \@Element("Text",filter="group2")
     *      {@*}
     *     public $test4;
     * }
     *
     * $form = new Form;
     * //this will assign 2 form elements at property "test" and "test2".
     * $form->addFromAnnotations("ExampleClass2", "group1");
     * //this will assign 2 form elements at property "test3" and "test4".
     * $form->addFromAnnotations("ExampleClass2", "group2");
     * //this will add a form element at property "test4",
     * $form->addFromAnnotations("ExampleClass2");
     *
     * </code>
     *
     * The filter property on the annotation and also the $filter argument may be an array. In this case
     * it is sufficient that a single filter value is available on either side for the form element to be assigned.
     * (The intersection of the two arrays must not be empty)
     *
     * If a form element was selected to be assigned to the form, the used property is looked for additional annotations
     * of the type "Loops\Annotations\Form\Validator" and "Loops\Annotations\Form\Filter".
     * These annotations also inherit from "Loops\Annotations\Object" and their resulting instances are added as validators
     * and filters to the created form element.
     * The same filter logic can also be used for this annotations.
     *
     * Example:
     * <code>
     *
     * use Loops\Annotations\Form\Element;
     * use Loops\Annotations\Form\Validator;
     * use Loops\Annotations\Form\Filter;
     * use Loops\Form;
     *
     * class ExampleClass3 {
     *     /**
     *      * \@Element("Text")
     *      * \@Validator("Required")
     *      * \@Filter("Select",elements={"a","b","c"})
     *      {@*}
     *     public $test;
     *
     *     /**
     *      * \@Element("Text",filter="group1")
     *      * \@Validator("Alphanumeric",filter="group1")
     *      {@*}
     *     public $test2;
     * }
     *
     * $form = new Form;
     * //this will assign form elements "test" with a validator and filter.
     * $form->addFromAnnotations("ExampleClass3");
     *
     * </code>
     *
     * If the passed $classname is an object and its class defines form events, they will also be registered
     * with this class.
     *
     * @param string|object $classname An object or a classname where annotations are collected from
     * @param string|array $filter Only use annotations that define at least one of the passed filter string
     * @return array The added form elements that were assigned to the form are returned in an array
     */
    public function addFromAnnotations($classname, $filter = "") {
        $loops = $this->getLoops();

        //init elements from annotations
        $properties = $loops->getService("annotations")->get(is_object($classname) ? get_class($classname) : $classname)->properties;

        $result = [];

        foreach($properties->find("Form\Element") as $key => $annotations) {
            foreach($annotations as $annotation) {
                if(!array_intersect((array)$annotation->filter, (array)$filter)) {
                    continue;
                }

                $element = $annotation->factory($this, $loops);
                $this->$key = $element;

                foreach($properties->$key->find("Form\Validator") as $annotation) {
                    if(!array_intersect((array)$annotation->filter, (array)$filter)) {
                        continue;
                    }

                    $element->addValidator($annotation->factory($element, $loops));
                }

                foreach($properties->$key->find("Form\Filter") as $annotation) {
                    if(!array_intersect((array)$annotation->filter, (array)$filter)) {
                        continue;
                    }

                    $element->addFilter($annotation->factory($element, $loops));
                }

                $result[$key] = $element;
                break;
            }
        }

        if(is_object($classname) && $classname !== $this) {
            $this->bindEventObject($classname, [ "Form\onValidate", "Form\onConfirm", "Form\onSubmit", "Form\onCleanup" ]);
        }

        foreach($this->getFormElements() as $name => $child) {
            if($this->value->offsetExists($name)) {
                $child->setValue($this->value->offsetGet($name));
            }
            else {
                $this->value->offsetSet($name, $child->getDefault());
            }
        }

        return $result;
    }

    /**
     * Checks passed input for its validity
     *
     * The passed input array may be a flat array. This is an array of a single dimension that
     * can be expanded into an array of multiple dimension. See the documentation of Loops\Misc::unflattenArray
     * for details on how this is done. The used delimiter for $value is "-" (dash character);
     *
     * If the passed input array does not specify all keys of the form elements, the missing values will be substituted
     * by the NULL variable.
     * The value of the input will then be assigned to the form elements. No hard filtering is used, the value will stay
     * as close as possible to its input value even if it failes validation.
     *
     * Each form element will validate its input and only if all values could be validated successfully, the "Form\onConfirm"
     * event will be triggered with the form value as the first argument and the form as the second.
     * All triggered must return a positive value or the input will not be considered as successfully validated.
     *
     * If the input is valid, the hard filter will be applied on the value.
     * (See documentation of the form element class for details)
     * The form value will now be in an optimal state and its confirmed property set to true.
     *
     * If the input could not be validated, its content will still be reflected into the form value.
     * However submission of the form is not possible until confirmation succeeds.
     *
     * @param array $values
     * @return bool TRUE if the input could be validated and the form is in confirmed state.
     */
    public function confirm($values) {
        $this->confirmed = FALSE;

        $values = Misc::unflattenArray($values, "-");

        foreach($this->getFormElements() as $name => $child) {
            $child->setValue(array_key_exists($name, $values) ? $values[$name] : NULL);
            $this->value->offsetSet($name, $child->getValue(FALSE));
        }

        if(!$this->validate()) {
            return FALSE;
        }

        if(!$this->fireEvent("Form\onConfirm", [ $this->value, $this ], TRUE, FALSE)) {
            return FALSE;
        }

        $this->applyFilter();

        return $this->confirmed = TRUE;
    }

    /**
     * Validates all form elements
     *
     * @return bool TRUE if the input could be validated.
     */
    public function validate() {
        $validated = TRUE;

        foreach($this->getFormElements() as $name => $child) {
            $validated &= $child->validate();
        }

        $validated &= $this->fireEvent("Form\onValidate", [ $this->value, $this ], TRUE, FALSE);

        return (bool)$validated;
    }

    /**
     * Undoes the confirmed status
     *
     * The form will leave confirmed status and has to be confirmed again in order to be submitted.
     */
    public function back() {
        $this->confirmed = FALSE;
    }

    /**
     * Updates the value and applies the hard filter
     *
     * All values from the form elements will be reflected into the form value.
     * The hard filter is applied, this may change the current value
     */
    public function applyFilter() {
        foreach($this->getFormElements() as $name => $child) {
            $this->value->offsetSet($name, $child->getValue(TRUE));
        }
    }

    /**
     * Submits the form
     *
     * This function can only be called if the form is in confirmed state.
     * Otherwise an exception will be thrown.
     *
     * The event "Form\onSubmit" event is triggered with the form value as the first argument and the form
     * as the second. In order to transit the form into submitted state, all listener functions must return
     * a value that evaluate to boolean true.
     * These listeners should take care about all data processing, for example by creating a new record
     * in the database.
     *
     * If submission was successful (all listeners returned true), the "Form\onCleanup" will be triggered
     * with the form as the first value. The form defines a listener that will forward this event to all assigned
     * form elements.
     * Allocated ressources should be freed. Then this method will return TRUE.
     *
     * @return bool Return true if submission of the form was successful and data has been processed.
     */
    public function submit() {
        if(!$this->confirmed) {
            throw new Exception("Form: Please call 'confirm' before calling 'submit'.");
        }

        $this->submitted = $this->fireEvent("Form\onSubmit", [ $this->getValue(), $this ], TRUE, FALSE);

        if($this->submitted) {
            $this->fireEvent("Form\onCleanup", [ $this ]);
        }

        return $this->submitted;
    }

    /**
     * The session will be forcefully initialized when rendering the form with the renderer
     *
     * @Listen("Renderer\onRender")
     */
    public function initFromSession() {
        $this->SessionTraitInitFromSession();
    }

    /**
     * Trigger the confirm action via URL
     *
     * The url must be exactly {$pagepath}/confirm where $pagepath is the location
     * of this form in the loops page structure.
     *
     * If not accessed by a POST request, the user will be redirected back to the form page.
     *
     * The POST data from the request is used to confirm the form.
     *
     * The url can't be accessd if no_confirm has beed set to true. Directly access
     * {$pagepath}/submit in this case.
     */
    public function confirmAction($parameter) {
        if($parameter || $this->no_confirm) {
            return;
        }

        $this->initFromSession();

        if($this->request->isPost()) {
            $this->confirm($this->request->post());
        }
        else if(!$this->confirmed) {
            return Misc::redirect($this, 302, $this->getLoops());
        }

        $this->saveToSession();

        return TRUE;
    }

    /**
     * Trigger the back action via URL
     *
     * The url must be exactly {$pagepath}/back where $pagepath is the location
     * of this form in the loops page structure.
     *
     * The user will be redirected back to the form at {$pagepath}.
     */
    public function backAction($parameter) {
        if($parameter) {
            return;
        }

        $this->initFromSession();

        $this->back();

        $this->saveToSession();

        return Misc::redirect($this, 302, $this->getLoops());
    }

    /**
     * Trigger the submit action via URL
     *
     * The url must be exactly {$pagepath}/submit where $pagepath is the location
     * of this form in the loops page structure.
     *
     * If not accessed by a POST request, the user will be redirected back to the form.
     * In this case, the confirmed state will also be reset and the form has to be confirmed
     * again.
     *
     * This url can be accessed directly with the input as POST data. However for this, the
     * no_confirm property has to be set to true. Otherwise the user will be redirected to the
     * form page.
     *
     * The session will also be cleared after successfully submitting the form leaving no user
     * input on next access.
     */
    public function submitAction($parameter) {
        if($parameter) {
            return;
        }

        $this->initFromSession();

        if(!$this->request->isPost()) {

            if($this->confirmed) {
                $this->back();
                $this->saveToSession();
            }

            return Misc::redirect($this);
        }

        if($this->no_confirm) {
            if(!$this->confirm($this->request->post())) {
                return TRUE;
            }
        }
        elseif(!$this->confirmed) {
            return Misc::redirect($this);
        }

        $this->submitted = $this->submit();

        if($this->no_confirm) {
            $this->confirmed = $this->submitted;
        }

        if($this->submitted) {
            $this->clearSession();
        }

        return TRUE;
    }

    /**
     * Return all form elements that were assigned to this form
     *
     * @return array The form elements as an array
     */
    public function getFormElements() {
        return iterator_to_array($this->getGenerator(TRUE, TRUE, "Loops\Form\Element", [ "value", "loopsid", "pagepath", "elements" ]));
    }

    /**
     * Returns the value of the form
     *
     * The value is an representation of the form input data or if no input has given to the form yet, the default
     * values.
     * The form value is stored in an object (entity). If an object was passed during form construction, this object will
     * be used as the form value.
     *
     * The value can also be retrieved as an array. In this case, the values will be hard filtered by default.
     * If you want to hard filter the values in the object (entity), use the applyFilter method.
     *
     * @param bool $array Specifies if the value should be returned as an array
     * @param bool $array_strict If set to true and an array is requested, the array will contain hard filtered values
     */
    public function getValue($array = FALSE, $array_strict = TRUE) {
        if($array) {
            $result = [];

            foreach($this->getFormElements() as $name => $child) {
                $result[$name] = $child->getValue($array_strict);
            }

            return $result;
        }
        else {
            return $this->value;
        }
    }

    /**
     * Return the current value as a form value
     *
     * A form value is a Loops\ArrayObject where the form that the value belongs to has been associated.
     * If the form contains Loops\Form\Element\SubForm elements, the values of these elements will also be
     * converted into form values.
     *
     * Form values are assembeled from the form elements and are objects on their own and thus do not
     * share features that the original entity may have (such as custom traversal).
     *
     * @param bool $array_strict If set to true, the form value will contain hard filtered values
     * @return Loops\Form\Value
     */
    public function getFormValue($strict = FALSE) {
        $result = [];

        foreach($this->getFormElements() as $name => $child) {
            $result[$name] = $child instanceof Subform ? $child->getFormValue($strict) : $child->getValue($strict);
        }

        return new Value($this, $result);
    }

    /**
     * The session trait directly sets the values - call setValue manually
     *
     * @Listen("Session\onInit")
     */
    public function onSessionInit($value) {
        if(!$value->offsetExists("value")) {
            return;
        }

        $value = $value->offsetGet("value");

        foreach($this->getFormElements() as $name => $child) {
            if(!$value->offsetExists($name)) continue;

            $newvalue = $value->offsetGet($name);

            $child->setValue($newvalue);

            //check if child did some processing/initializing on object values - keep sync

            $childvalue = $child->getValue();

            if($newvalue !== $childvalue) {
                $value->offsetSet($name, $childvalue);
            }
        }
    }

    /**
     * Forward "Form\onCleanup" event to all form elements
     *
     * @Listen("Form\onCleanup")
     */
    protected function cleanupChildren() {
        foreach($this->getFormElements() as $child) {
            $child->fireEvent("Form\onCleanup", [ $child, $this ]);
        }
    }
}
