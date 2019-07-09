<?php

namespace Manju\Traits;

use Manju\{
    Bun, Converters\Date, Converters\Number, Converters\Text, Exceptions\ManjuException, Helpers\BeanHelper,
    Interfaces\AnnotationFilter, Interfaces\Converter, ORM, ORM\Model
};
use NGSOFT\Tools\Reflection\Parser,
    Psr\Cache\CacheItemPoolInterface,
    RedBeanPHP\SimpleModel,
    ReflectionClass,
    SplFileInfo;
use const day;
use function NGSOFT\Tools\{
    findClassesImplementing, toSnake
};

trait Metadata {

    /**
     * Get Model Metadatas
     * @param string|null $key
     * @return mixed
     */
    public function getMeta(string $key = null) {
        $meta = BeanHelper::$metadatas[get_class($this)];
        if ($key === null) return $meta;
        return [$key] ?? null;
    }

}
