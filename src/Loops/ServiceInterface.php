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

use Loops;

/**
 * This interface must be implemented if your class is an autoloadable Loops service
 *
 * The interface defines a factory method and provides a way to tell the Loops context if
 * is is a shared service.
 */
interface ServiceInterface {
    /**
     * Defines if the service is shared or not
     *
     * @param Loops The loops context
     * @return bool This method should return TRUE if the service is a shared service
     */
    public static function isShared(Loops $loops);
    
    /**
     * Factory method of the service
     *
     * @param Loops\ArrayObject $config Configuration passed by the user
     * @param Loops The loops context
     * @return object This method should return the service instance
     */
    public static function getService(ArrayObject $config, Loops $loops);
    
    /**
     * Defines if the service can be created
     *
     * This function should check if the dependencies to create this service
     * are met and return FALSE if not. For example if the service relies on
     * external classes that need to be installed seperately, this function
     * should check via class_exists if the classes are present.
     * In general, if the service can not be created because of missing
     * configuration values, this function should return TRUE and throw an
     * exception in the getService function.
     *
     * @param Loops The loops context
     * @return bool This method should return if the service can be created
     */
    public static function hasService(Loops $loops);
}