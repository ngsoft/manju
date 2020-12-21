<?php

namespace Manju\Interfaces;

use Manju\Reflection\Annotation;

interface AnnotationFilter {

    /**
     * Translated key into metadatas
     * @return string
     */
    public function getKey(): string;

    /**
     * Process Annotation list
     * @param Annotation[] $data
     * @param array $meta
     */
    public function process(array $data, array &$meta);
}
