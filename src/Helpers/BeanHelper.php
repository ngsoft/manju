<?php

declare(strict_types=1);

namespace Manju\Helpers;

use Manju\{
    Interfaces\AnnotationFilter, Interfaces\Converter, ORM\Model
};
use RedBeanPHP\BeanHelper\SimpleFacadeBeanHelper;
use function Manju\{
    autoloadDir, findClassesImplementing
};

final class BeanHelper extends SimpleFacadeBeanHelper {

    /** @var array<string,Converter> */
    public static $converters = [];

    /** @var AnnotationFilter[] */
    public static $filters = [];

    /** @var array<string,\stdClass> */
    public static $metadatas = [];

    /** @var Model|null */
    protected static $for = null;

    /**
     * Loads Converters
     * @param string $path Path to class implementing Converter
     */
    public static function loadConverters(string $path = null) {
        $path = $path ?? dirname(__DIR__) . '/Converters';

        if (is_dir($path)) autoloadDir($path);
        foreach (findClassesImplementing(Converter::class) as $classname) {
            self::$converters[$classname] = $classname;
            foreach ($classname::getTypes() as $keyword) {
                self::$converters[$keyword] = $classname;
            }
        }
    }

    public static function loadFilters(string $path = null) {
        $path = $path ?? dirname(__DIR__) . '/Filters';
        if (is_dir($path)) autoloadDir($path);

        foreach (findClassesImplementing(AnnotationFilter::class) as $classname) {
            self::$filters[] = new $classname();
        }
    }

    public function __construct() {
        if (empty(self::$converters)) {
            self::loadConverters();
            self::loadFilters();
        }
    }

}
