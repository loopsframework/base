<?php

namespace Pages;

use Loops\Page;
use Loops\Form as LoopsForm;
use Loops\Annotations\Access\Expose;
use Loops\Annotations\Element\Form;
use Loops\Annotations\Form\Element;


class SimpleTestForm extends LoopsForm {
    /**
     * @Element("Number")
     */
    protected $test;
}

class SimpleTestFormSkip extends LoopsForm {
    /**
     * @Element("Number")
     */
    protected $test;

    protected $no_confirm = TRUE;
}


class Testform extends Page {
    /**
     * @Form("SimpleTestForm")
     */
    protected $form;

    /**
     * @Form("SimpleTestFormSkip")
     */
    protected $form2;
}
