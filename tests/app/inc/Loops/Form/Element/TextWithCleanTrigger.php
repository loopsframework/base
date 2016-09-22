<?php

namespace Loops\Form\Element;

use Loops\Annotations\Event\Form\onCleanup;

class TextWithCleanTrigger extends Text {
    /**
     * @onCleanup
     */
    public function cleanTrigger($element, $form) {
        echo get_class($element);
        echo get_class($form);
    }
}
