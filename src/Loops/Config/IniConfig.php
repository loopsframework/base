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

use Loops\Exception;

class IniConfig extends ArrayConfig {
    public function __construct($inifile) {
        $array = parse_ini_file($inifile, TRUE);

        if(!is_array($array)) {
            throw new Exception("Failed to parse ini file '$inifile'.");
        }

        parent::__construct($array);
    }
}