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

}
