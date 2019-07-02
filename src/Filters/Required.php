<?php

namespace Manju\Filters;

use Manju\Helpers\AnnotationFilterAbstract;
use Manju\ORM\Model;
use NGSOFT\Tools\Reflection\Annotation;

class Required extends AnnotationFilterAbstract {

    /** {@inheritdoc} */
    public $types = ["CLASS", "PROPERTY"];

    /** {@inheritdoc} */
    public $tags = ["required"];

    public function handle(Annotation $annotation, array &$meta) {

        if ($annotation->annotationType === "CLASS") $props = (array) $annotation->value;
        else $props = (array) $annotation->attributeName;
        $props = array_filter($props, function ($prop) use($meta) {
            return in_array($prop, $meta["properties"]) and preg_match(Model::VALID_PARAM, $prop) > 0;
        });

        $meta["required"] = array_merge($meta["required"], $props);
    }

}
