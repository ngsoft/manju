<?php

namespace Manju\Interfaces;

use NGSOFT\Manju\Reflection\Annotation;

interface AnnotationFilter {

    /**
     * Process Annotation list
     * @param Annotation[] $data
     * @param array $meta
     */
    public function process(array $data, array &$meta);
}
