<?php

namespace Manju\Filters;

use Manju\Helpers\AnnotationFilterAbstract;
use Manju\ORM\Model;
use NGSOFT\Tools\Reflection\Annotation;

class Relation extends AnnotationFilterAbstract {

    /** {@inheritdoc} */
    public $types = ["PROPERTY"];

    /** {@inheritdoc} */
    public $tags = ["relation"];

    /** {@inheritdoc} */
    public function handle(Annotation $annotation, array &$meta) {
        print_r($annotation);
    }

}
