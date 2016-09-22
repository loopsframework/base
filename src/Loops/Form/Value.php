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

namespace Loops\Form;

use ArrayObject as StdArrayObject;
use Loops\Form;
use Loops\ArrayObject;

class Value extends ArrayObject {
    /**
     * @var array Associated forms
     *
     * Store the form associations into a static property.
     * This way no property names will interfere with keys that may be used in the array object.
     * Note: PHP can have properties and static properties of the same name
     */
    private static $forms = [];

    public function __construct(Form $form, $array = [], $flags = StdArrayObject::ARRAY_AS_PROPS, $iterator_class = "ArrayIterator") {
        parent::__construct($array, $flags, $iterator_class);
        self::$forms[spl_object_hash($this)] = $form;
    }

    public function getForm() {
        return self::$forms[spl_object_hash($this)];
    }

    public function __destruct() {
        unset(self::$forms[spl_object_hash($this)]);
    }
}
