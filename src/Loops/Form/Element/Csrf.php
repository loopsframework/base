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

namespace Loops\Form\Element;

use Loops;
use Loops\Annotations\Listen;
use Loops\Annotations\Access\ReadOnly;
use Loops\Annotations\Access\Expose;
use Loops\Annotations\Session\SessionVar;
use Loops\Session\SessionTrait;

class Csrf extends Text {
    use SessionTrait;
    
    /**
     * @ReadOnly("getToken")
     * @SessionVar
     * @Expose
     */
    protected $token;
    
    public function getToken() {
        $this->initFromSession();
        
        if(!$this->token) {
            $this->token = md5(uniqid());
            $this->saveToSession();
        }
        
        return $this->token;
    }
    
    /**
     * @Listen("Form\onValidate")
     */
    public function onValidate($value) {
        if($this->getToken() != $value) {
            $this->messages->add("CSRF validation failed.");
            return FALSE;
        }
        
        return TRUE;
    }
}