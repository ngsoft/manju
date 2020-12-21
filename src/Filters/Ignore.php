<?php

namespace Manju\Filters;

use Manju\{
    Helpers\AnnotationFilterAbstract, ORM\Model
};
use Manju\Reflection\Annotation;

class Ignore extends AnnotationFilterAbstract {

    /** {@inheritdoc} */
    public $types = ["CLASS", "PROPERTY"];

    /** {@inheritdoc} */
    public $tags = ["ignore"];

    /** {@inheritdoc} */
    public function handle(Annotation $annotation, array &$meta) {
        /** @ignore (param1, param2) */
        if ($annotation->annotationType === "CLASS") $props = (array) $annotation->value;
        /** @ignore */
        elseif ($annotation->value !== false) $props = (array) $annotation->attributeName;
        /** @ignore false */
        else $props = [];
        $props = array_filter($props, function ($prop) use($meta) {
            return in_array($prop, $meta["properties"]) and preg_match(Model::VALID_PARAM, $prop) > 0;
        });
        $meta["ignore"] = $meta["ignore"] ?? [];
        $meta["ignore"] = array_merge($meta["ignore"], $props);
    }

}
