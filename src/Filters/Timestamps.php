<?php

namespace Manju\Filters;

use Manju\Helpers\AnnotationFilterAbstract,
    Manju\Reflection\Annotation;

class Timestamps extends AnnotationFilterAbstract {

    /** {@inheritdoc} */
    public $types = ["CLASS"];

    /** {@inheritdoc} */
    public $tags = ["timestamps"];

    /** {@inheritdoc} */
    public function handle(Annotation $annotation, array &$meta) {
        $meta["timestamps"] = $annotation->value !== false;
    }

}
