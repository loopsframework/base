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

namespace Loops\Service;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\SimpleAnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Loops;
use Loops\Service;
use Loops\ArrayObject;

class DoctrineAnnotationReader extends Service {
    public static function annotationAutoloader($classname) {
        if(substr($classname, 0, 18) == "Loops\Annotations\\") {
            return class_exists($classname);
        }
        
        return FALSE;
    }

    public static function getService(ArrayObject $config, Loops $loops) {
        if(@$config->simple) {
            $annotation_reader = new SimpleAnnotationReader;
            $annotation_reader->addNamespace("Loops\Annotations");
            $annotation_reader->addNamespace("Loops\Annotations\Access");
            $annotation_reader->addNamespace("Loops\Annotations\Admin");
            $annotation_reader->addNamespace("Loops\Annotations\Element");
            $annotation_reader->addNamespace("Loops\Annotations\Element\Doctrine");
            $annotation_reader->addNamespace("Loops\Annotations\Event\Form");
            $annotation_reader->addNamespace("Loops\Annotations\Event\Renderer");
            $annotation_reader->addNamespace("Loops\Annotations\Event\Session");
            $annotation_reader->addNamespace("Loops\Annotations\Form");
            $annotation_reader->addNamespace("Loops\Annotations\Form\Element");
            $annotation_reader->addNamespace("Loops\Annotations\Navigation");
            $annotation_reader->addNamespace("Loops\Annotations\Session");
            return $annotation_reader;
        }
        else {
            return new AnnotationReader;
        }
    }
}

AnnotationRegistry::registerLoader(["Loops\Service\DoctrineAnnotationReader", "annotationAutoloader"]);