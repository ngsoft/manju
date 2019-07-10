<?php

namespace Manju\Helpers;

use Manju\Interfaces\AnnotationFilter;
use NGSOFT\Tools\Reflection\Annotation;

abstract class AnnotationFilterAbstract implements AnnotationFilter {

    /** @var array<?string> */
    public $types = [];

    /** @var array<string> */
    public $tags = [];

    /**
     * Handles a single annotation
     * @param Annotation $annotation
     * @param array $meta
     */
    abstract public function handle(Annotation $annotation, array &$meta);

    /** {@inheritdoc} */
    public function process(array $data, array &$meta) {
        foreach ($data as $annotation) {
            if (
                    in_array($annotation->annotationType, $this->types)
                    and in_array($annotation->tag, $this->tags)
            ) {
                $this->handle($annotation, $meta);
            }
        }
    }

    public function __construct() {
        $types = Annotation::ANNOTATION_TYPES;
        $accepted = array_map(function ($type) use ($types) {
            if (isset($types[$type])) $type = $types[$type];
            elseif (!in_array($type, $types)) {
                $type = null;
            }
            return $type;
        }, $this->types);
        $this->types = array_filter($accepted, "is_string");
    }

}
