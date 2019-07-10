<?php

namespace Manju\Filters;

use Manju\{
    Exceptions\ManjuException, Helpers\AnnotationFilterAbstract, ORM\Model
};
use NGSOFT\Tools\Reflection\Annotation;
use function NGSOFT\Tools\findClassesImplementing;

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
            try {
                if (
                        isset($relation["target"])
                        and ( $relation["target"] = $this->getExpandedModelClass($relation["target"]) )
                ) {

                    $prop = $annotation->attributeName;
                    if (($index = array_search($prop, $meta["properties"])) !== false) {
                        unset($meta["properties"][$index]);
                        unset($meta["converters"][$prop]);
                        ksort($meta["properties"]);
                    }
                    $meta["relations"][$prop] = $relation;
                }
            } catch (ManjuException $exc) {
                $exc->getCode();
            }
        }
    }

    /**
     * Finds Model corresponding to the relation
     * @param string $target
     * @throws ManjuException
     * @return string
     */
    private function getExpandedModelClass(string $target): string {

        $norm = preg_replace('/^(?:.*[\\\])?(\w+)$/', '$1', strtolower($target));
        foreach (findClassesImplementing(Model::class) as $model) {
            $mnorm = preg_replace('/^(?:.*[\\\])?(\w+)$/', '$1', strtolower($model));
            if ($norm === $mnorm) return $model;
        }
        // logs error if logger registered
        throw new ManjuException("Cannot find Model Class For Relation with target $target");
    }

}
