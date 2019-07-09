<?php

namespace Manju\Filters;

use Manju\Helpers\AnnotationFilterAbstract;
use Manju\ORM\Model;
use NGSOFT\Tools\Reflection\Annotation;

/**
 * Change Model::__set() and Model::__get() behaviours
 */
class Access extends AnnotationFilterAbstract {

    /** {@inheritdoc} */
    public $types = ["CLASS"];

    /** {@inheritdoc} */
    public $tags = ["property", "property-read", "property-write"];

    /** {@inheritdoc} */
    public function handle(Annotation $annotation, array &$meta) {

        if (
                is_array($annotation->value)
                and isset($annotation->value["param"])
                and in_array($annotation->value["param"], $meta["properties"])
        ) {
            switch ($annotation->tag) {
                case "property-read":
                    $meta["access"][$annotation->value["param"]] = Model::AUTO_PROPERTY_READ;
                    break;
                case "property-write":
                    $meta["access"][$annotation->value["param"]] = Model::AUTO_PROPERTY_WRITE;
                    break;
                default :
                    $meta["access"][$annotation->value["param"]] = Model::AUTO_PROPERTY_BOTH;
            }
        }
    }

}
