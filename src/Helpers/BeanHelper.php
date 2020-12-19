<?php

declare(strict_types=1);

namespace Manju\Helpers;

use Manju\{
    Converters\Text, Exceptions\ManjuException, Interfaces\AnnotationFilter, Interfaces\Converter, ORM, ORM\Model, Reflection\Parser
};
use Psr\Cache\{
    CacheItemInterface, CacheItemPoolInterface
};
use RedBeanPHP\{
    BeanHelper\SimpleFacadeBeanHelper, Facade, OODBBean, SimpleModel
};
use ReflectionClass,
    SplFileInfo,
    Throwable;
use function Manju\{
    array_to_object, autoloadDir, findClassesImplementing, toSnake
};

final class BeanHelper extends SimpleFacadeBeanHelper {

    /** @var array<string,string> */
    protected static $models = [];

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

    /**
     * Loads AnnotationFilter
     * @param string $path Path to class implementing AnnotationFilter
     */
    public static function loadFilters(string $path = null) {
        $path = $path ?? dirname(__DIR__) . '/Filters';
        if (is_dir($path)) autoloadDir($path);

        foreach (findClassesImplementing(AnnotationFilter::class) as $classname) {
            self::$filters[] = new $classname();
        }
    }

    /**
     * Adds a Model Search Path
     * @param string ...$path
     */
    public function addSearchPath(string ...$paths) {

        foreach ($paths as $path) {
            $real = realpath($path);
            if (($real == false) or!is_dir($real)) throw new ManjuException("Invalid model search path $path.");
            autoloadDir($path);
        }
        foreach (findClassesImplementing(Model::class) as $classname) {
            $model = new $classname();
            self::addModel($model);
        }
    }

    ///////////////////////////////// ToolBox  /////////////////////////////////

    /**
     * Dispense or loads a bean for a given model
     * @param Model $model
     * @param int|null $id
     */
    public static function dispenseFor(Model $model, int $id = null) {

        if (($type = $model->getMeta("type"))) {
            self::$for = $model;
            if (is_int($id)) Facade::load($type, $id);
            else Facade::dispense($type);
        }
    }

    /**
     * Add a model to the list
     * @param Model $model
     */
    public static function addModel(Model $model) {
        $classname = get_class($model);
        if (!isset(self::$metadatas[$classname])) self::buildMetaDatas($model);
        if ($type = $model->getMeta("type")) self::$models[$type] = $classname;
    }

    private static function buildMetaDatas(Model $model) {
        $classname = get_class($model);
        if (isset(self::$metadatas[$classname])) return;
        $refl = new ReflectionClass($model);
        $cache = ORM::getCachePool();
        $item = null;
        //loads from cache
        if ($cache instanceof CacheItemPoolInterface) {
            $fileinfo = new SplFileInfo($refl->getFileName());
            //using modified time to miss on modified model to reload new metadatas
            $key = md5($fileinfo->getMTime() . $fileinfo->getPathname());
            $item = $cache->getItem($key);
            if ($item->isHit()) {
                self::$metadatas[$classname] = array_to_object($item->get());
                return;
            }
        }

        //Create metadata object
        $meta = [
            //table name
            "type" => null,
            //property list
            "properties" => [],
            //properties data types
            "converters" => [],
            // unique values
            "unique" => [],
            //not null values
            "required" => [],
            //enables created_at and updated_at
            "timestamps" => false
        ];



        //set type(table) (without annotations)
        if (
                isset($classname::$type)
                and preg_match(Model::VALID_BEAN, $classname::$type)
        ) {
            $meta["type"] = $classname::$type;
        } else {
            $short = $refl->getShortName();
            $snake = preg_replace('/_?(model|entity)_?/', "", toSnake($short));
            $parts = explode("_", $snake);
            $type = array_pop($parts);
            if (preg_match(Model::VALID_BEAN, $type)) $meta["type"] = $type;
        }

        //reads properties from model class
        foreach ($refl->getProperties() as $prop) {
            if (
                    ($prop->class !== Model::class )
                    and ( $prop->class !== SimpleModel::class )
                    and $prop->isProtected()
                    and!$prop->isPrivate()
                    and!$prop->isStatic()
            ) {
                $meta["properties"][] = $prop->name;
                $meta["converters"][$prop->name] = Text::class;

                // if typed default value
                $prop->setAccessible(true);
                $val = $prop->getValue($model);
                $type = gettype($val);
                if (isset(self::$converters[$type])) $meta["converters"][$prop->name] = self::$converters[$type];
            }
        }

        //Reads annotations
        $parser = new Parser();
        if ($annotations = $parser->ParseAll($refl)) {
            //parse only extended Models, not base models annotations
            $annotations = array_filter($annotations, function ($ann) {
                return !in_array($ann->reflector->class ?? "", [Model::class, SimpleModel::class]);
            });
            foreach ($annotations as $annotation) {
                if (
                        $annotation->annotationType === "PROPERTY"
                        and ( $annotation->tag === "var" or $annotation->tag === "converter")
                        and in_array($annotation->attributeName, $meta["properties"])
                ) {
                    if (is_string($annotation->value)) {
                        $value = preg_replace('/^[\\\]?([a-zA-Z]+).*?$/', "$1", $annotation->value);
                        if (isset($converters[$value])) {
                            $meta["converters"][$annotation->attributeName] = $converters[$value];
                        }
                    }
                }
            }
            foreach ($filters as $filter) {
                $filter->process($annotations, $meta);
            }
        }

        if (isset($meta["ignore"])) {
            foreach ($meta["ignore"] as $key) {
                unset($meta["converters"][$key]);
                foreach (["properties", "required", "unique"] as $metakey) {
                    $meta[$metakey] = array_filter($meta[$metakey], function ($val) use($key) { return $key !== $val; });
                }
            }
            unset($meta["ignore"]);
        }


        //save to cache
        if ($item instanceof CacheItemInterface) {
            $item->set($meta);
            $cache->save($item);
        }

        self::$metadatas[$classname] = array_to_object($meta);
    }

    ///////////////////////////////// RedBean Overrides  /////////////////////////////////

    /** {@inheritdoc} */
    public function getModelForBean(OODBBean $bean) {
        $type = $bean->getMeta('type');
        if (preg_match(Model::VALID_BEAN, $type)) {
            try {
                if (self::$for instanceof Model) {
                    $model = self::$for;
                    self::$for = null;
                } elseif (isset(self::$models[$type])) {
                    $classname = self::$models[$type];
                    $model = new $classname();
                } else throw new ManjuException("Cannot find any model with type $type");
            } catch (Throwable $exc) { $exc->getCode(); }
            if (isset($model)) {
                $model->loadBean($bean);
                return $model;
            }
        }
        return parent::getModelForBean($bean);
    }

    ///////////////////////////////// Initialisation  /////////////////////////////////

    public function __construct() {
        if (empty(self::$converters)) {
            self::loadConverters();
            self::loadFilters();
        }
        if (Facade::getRedBean()->getBeanHelper() !== $this) {
            Facade::getRedBean()->setBeanHelper($this);
        }
    }

}
