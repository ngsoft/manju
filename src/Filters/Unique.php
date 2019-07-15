<?php

namespace Manju\Filters;

use Manju\Helpers\AnnotationFilterAbstract;
use Manju\ORM\Model;
use NGSOFT\Manju\Reflection\Annotation;

class Unique extends AnnotationFilterAbstract {

    /** {@inheritdoc} */
    public $types = ["CLASS", "PROPERTY"];

    /** {@inheritdoc} */
    public $tags = ["unique"];

    /** {@inheritdoc} */
    public function handle(Annotation $annotation, array &$meta) {

        if ($annotation->annotationType === "CLASS") $props = (array) $annotation->value;
        elseif ($annotation->value !== false) $props = (array) $annotation->attributeName;
        else $props = [];
        $props = array_filter($props, function ($prop) use($meta) {
            return in_array($prop, $meta["properties"]) and preg_match(Model::VALID_PARAM, $prop) > 0;
        });

        $meta["unique"] = array_merge($meta["unique"], $props);
    }

}
