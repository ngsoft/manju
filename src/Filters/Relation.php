<?php

namespace Manju\Filters;

use Manju\Helpers\AnnotationFilterAbstract,
    NGSOFT\Tools\Reflection\Annotation;

class Relation extends AnnotationFilterAbstract {

    /** {@inheritdoc} */
    public $types = ["PROPERTY"];

    /** {@inheritdoc} */
    public $tags = ["relation"];

    /** {@inheritdoc} */
    public function handle(Annotation $annotation, array &$meta) {
        /**
         * valid
         * @relation RelationType(target= "target", param1="value1")
         * @relation RelationType(target)
         */
        $val = $annotation->value;
        if (
                is_array($val) and count($val) === 1
                and $type = key($val) and is_string($type)
                and is_array($val[$type])
        ) {
            $relation = [];
            $relation["type"] = $type;
            foreach ($val[$type] as $param => $value) {
                if (is_int($param) and is_string($value)) {
                    $relation["target"] = $value;
                } elseif (is_string($param)) $relation[$param] = $value;
            }
            if (isset($relation["target"])) {
                $prop = $annotation->attributeName;
                if (($index = array_search($prop, $meta["properties"])) !== false) {
                    unset($meta["properties"][$index]);
                    unset($meta["converters"][$prop]);
                    ksort($meta["properties"]);
                }
                $meta["relations"][$prop] = $relation;
            }
        }
    }

}
