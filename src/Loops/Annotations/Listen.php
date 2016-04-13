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

namespace Loops\Annotations;

use Loops\Exception;

/**
 * @Annotation
 * @Target({"PROPERTY","METHOD"})
 */
class Listen {
    /**
     * @var string
     * @Required
     */
    public $value;
    
    public function __construct($options) {
        if(empty($options) && get_class($this) != __CLASS__) {
            $this->value = substr(get_class($this), strlen(__CLASS__));
        }
        elseif(!empty($options["value"])) {
            $this->value = $options["value"];
        }
        else {
            throw new Exception("Invalid listener.");
        }
    }
}