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

namespace Loops\Doctrine;

use ArrayAccess;
use ReflectionClass;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Loops;
use Loops\Exception;
use Loops\Form;
use Loops\Annotations\Listen;
use Loops\Form\Element\DoctrineEntitySelect;
use Loops\Form\Element\Text;
use Loops\Form\Element\TextArea;
use Loops\Form\Element\Number;
use Loops\Form\Element\Validator\NotNull;
use Loops\Form\Element\Validator\Length;

/**
 * An intermediate class that automatically sets up missing data of form elements by
 * analyzing doctrine metadata
 */
class EntityForm extends Form {
    public function __construct(ArrayAccess $entity = NULL, $filter = "", $fields = TRUE, $context = NULL, Loops $loops = NULL) {
        parent::__construct($entity, $filter, $context, $loops);

        self::enhanceFromEntity($this, $entity, $filter, $fields, $this->getLoops());
    }

    /**
     * @Listen("Session\onInit")
     */
    public function initDoctrineService($value) {
        //make sure doctrines proxy autoloader is ready
        $doctrine = $this->getLoops()->getService("doctrine");
    }

    public static function enhanceFromEntity(Form $form, $entity, $filter = "", $fields = TRUE, Loops $loops = NULL) {
        if(!$loops) {
            $loops = Loops::getCurrentLoops();
        }

        //get metadata from doctrine
        $classname = EntityList::getEntityClassname($entity, $loops);

        if(!is_object($entity)) {
            $entity = $classname;
        }

        $properties = $loops->getService("annotations")->get($classname)->properties;

        $metadata = $loops->getService("doctrine")->getMetadataFactory()->getMetadataFor($classname);

        //find elements
        $elements = $form->getFormElements();

        //add missing fields that are requested and have metadata
        if($fields === TRUE) {
            $missing_fields = array_diff(array_keys($metadata->fieldMappings), array_keys($elements));
        }
        else {
            $missing_fields = array_intersect(array_diff((array)$fields, array_keys($elements)), array_keys($metadata->fieldMappings));
        }

        foreach($missing_fields as $name) {
            if(!empty($metadata->fieldMappings[$name]['id'])) {
                continue;
            }

            if(!$entity->offsetExists($name)) {
                $classname = get_class($entity);
                throw new Exception("Failed to create Element for Entity '$classname'. Property '$name' is not accessible.");
            }

            $elements[$name] = self::addMissingElement($form, $entity, $loops, $name, $metadata->fieldMappings[$name], $properties->$name, $filter, FALSE);

            if(!$form->value->offsetExists($name)) {
                $form->value->offsetSet($name, $elements[$name]->getValue());
            }
        }

        //add missing accociations
        $missing_assoc = array_intersect(array_diff((array)$fields, array_keys($elements)), array_keys($metadata->associationMappings));

        foreach($missing_assoc as $name) {
            $elements[$name] = self::addMissingElement($form, $entity, $loops, $name, $metadata->associationMappings[$name], $properties->$name, $filter, TRUE);

            if(!$form->value->offsetExists($name)) {
                $form->value->offsetSet($name, $elements[$name]->getValue());
            }
        }

        //enhance fields
        foreach($elements as $name => $element) {
            self::enhanceLabelFromName($element, $name);

            if(array_key_exists($name, $metadata->fieldMappings)) {
                self::enhanceDescriptionFromComment($element, $metadata->fieldMappings[$name]);
            }
        }
    }

    private static function addMissingElement($form, $entity, $loops, $name, $metadata, $annotations, $filter, $assoc) {
        //get default value for element
        if(is_object($entity) && $entity instanceof ArrayAccess) {
            $default = $entity->offsetGet($name);
        }
        elseif(is_string($entity)) {
            $reflection = new ReflectionClass($entity);
            $defaults = $reflection->getDefaultProperties();
            $default = $defaults[$name];
        }
        else {
            throw new Exception("Internal error. Bad entity.");
        }

        if($assoc) {
            $element = self::createElementFromMetadataAssoc($form, $loops, $metadata, $default);
        }
        else {
            $element = self::createElementFromMetadata($form, $loops, $metadata, $default);
        }

        //add additional validators
        foreach($annotations->find("Form\Validator") as $annotation) {
            if(!array_intersect((array)$annotation->filter, (array)$filter)) {
                continue;
            }

            $element->addValidator($annotation->factory($element, $loops));
        }

        //add additional filters from annotation
        foreach($annotations->find("Form\Filter") as $annotation) {
            if(!array_intersect((array)$annotation->filter, (array)$filter)) {
                continue;
            }

            $element->addFilter($annotation->factory($element, $loops));
        }

        $form->offsetSet($name, $element);

        return $element;
    }

    private static function createElementFromMetadataAssoc($form, $loops, $metadata, $default) {
        $prefix = $loops->getService("doctrine")->entity_prefix;
        $target_entity = $metadata["targetEntity"];

        if(substr($target_entity, 0, strlen($prefix)) != $prefix) {
            throw new Exception("Relation to unsopported Entity.");
        }

        if(count($metadata["joinColumns"]) > 1) {
            throw new Exception("Multiple join columns are not supported.");
        }

        if($metadata["type"] == ClassMetadataInfo::MANY_TO_ONE) {
            $element = new DoctrineEntitySelect(substr($target_entity, strlen($prefix)), $default, $metadata["joinColumns"][0]["nullable"], [], [], $form, $loops);
        }
        else {
            throw new Exception("Doctrine association type '$metadata[type]' is not implemented yet.");
        }

        return $element;
    }

    private static function createElementFromMetadata($form, $loops, $metadata, $default) {
        if($metadata["type"] == "string") {
            $element = new Text($default, [], [], $form, $loops);
        }
        elseif($metadata["type"] == "text") {
            $element = new TextArea($default, [], [], $form, $loops);
        }
        elseif($metadata["type"] == "integer") {
            $element = new Number($default, [], [], $form, $loops);
        }
        else {
            throw new Exception("Not implemented. (Create element from doctrine metadata of type '$metadata[type]')");
        }

        if(!$metadata["nullable"]) {
            $element->addValidator(new NotNull($loops));
        }

        if($metadata["length"]) {
            $element->addValidator(new Length(0, $metadata["length"], $loops));
        }

        if($metadata["unique"]) {
            throw new Exception("Implement unique validator.");
        }

        return $element;
    }

    private static function enhanceLabelFromName($element, $name) {
        if($element->label) return;
        $element->label = $name;
    }

    private static function enhanceDescriptionFromComment($element, $mapping) {
        if(empty($mapping["options"]["comment"])) return;
        if($element->description) return;
        $element->description = $mapping["options"]["comment"];
    }
}
