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

namespace Loops\Messages;

use Countable;
use IteratorAggregate;
use ArrayIterator;

class MessageList implements IteratorAggregate, Countable {
    private $messages = [];
    private $default_severity;

    public function __construct($default_severity = Message::INFO) {
        $this->default_severity = $default_severity;
    }

    public function add($value, $severity = NULL) {
        if(!($value instanceof Message)) {
            $value = new Message((string)$value, $severity === NULL ? $this->default_severity : $severity);
        }

        $this->messages[] = $value;

        return $value;
    }

    public function count() {
        return count($this->messages);
    }

    public function getIterator() {
        return new ArrayIterator($this->messages);
    }
}
