<?php

namespace Manju\Filters;

use Manju\{
    Helpers\AnnotationFilterAbstract, ORM\Model, Reflection\Annotation
};

class Type extends AnnotationFilterAbstract {

    /** {@inheritdoc} */
    public $types = ["CLASS"];

    /** {@inheritdoc} */
    public $tags = ["table", "type"];

    /** {@inheritdoc} */
    public function handle(Annotation $annotation, array &$meta) {
        if (is_string($annotation->value) and preg_match(Model::VALID_BEAN, $annotation->value)) {
            $meta["type"] = $annotation->value;
        }
    }

}
