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

namespace Loops\Config;

use Loops\ArrayObject;

class ArrayConfig extends ArrayObject {
    public function __construct($array = []) {
        foreach($array as $key => $value) {
            if(!is_array($value)) {
                continue;
            }
            
            $array[$key] = new ArrayConfig($value);
        }
        
        parent::__construct($array);
    }
}