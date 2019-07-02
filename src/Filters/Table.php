<?php

namespace Manju\Filters;

use Manju\Helpers\AnnotationFilterAbstract;
use Manju\ORM\Model;
use NGSOFT\Tools\Reflection\Annotation;

class Table extends AnnotationFilterAbstract {

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
