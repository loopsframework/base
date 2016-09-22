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

namespace Loops\Http;

class File {
    private $name;
    private $type;
    private $location;
    private $error;
    private $size;

    public function __construct($file_array) {
        $this->name = $file_array["name"];
        $this->type = $file_array["type"];
        $this->location = $file_array["tmp_name"];
        $this->size = $file_array["size"];
        $this->error = $file_array["error"];
    }

    public function moveTo($target) {
        if(!move_uploaded_file($this->location, $target)) {
            return FALSE;
        }

        $this->location = $target;
        return TRUE;
    }

    public function getError() {
        return $this->error;
    }

    public function getLocation() {
        return $this->location;
    }

    public function getName() {
        return $this->name;
    }

    public function getType() {
        return $this->type;
    }

    public function getSize() {
        return $this->size;
    }
}
