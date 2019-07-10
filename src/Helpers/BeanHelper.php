<?php

namespace Manju\Helpers;

use Manju\{
    Bun, Converters\Date, Converters\Number, Converters\Text, Exceptions\ManjuException, Interfaces\AnnotationFilter,
    Interfaces\Converter, ORM, ORM\Model
};
use NGSOFT\Tools\{
    Reflection\Parser, Traits\Logger
};
use Psr\{
    Cache\CacheItemPoolInterface, Log\LoggerAwareTrait
};
use RedBeanPHP\{
    BeanHelper\SimpleFacadeBeanHelper, OODBBean, SimpleModel
};
use ReflectionClass,
    SplFileInfo,
    Throwable;
use const day;
use function NGSOFT\Tools\{
    autoloadDir, findClassesImplementing, toSnake
};

class BeanHelper extends SimpleFacadeBeanHelper {

    use Logger,
        LoggerAwareTrait;

    /** @var array<string,string> */
    protected static $models = [];

    /** @var array<string,array> */
    public static $metadatas = [];

    /** @var array<string,Converter> */
    public static $converters = [];

    /** @var array<AnnotationFilter> */
    public static $filters = [];

    /** @var Model|null */
    protected static $for;

    /** {@inheritdoc} */
    public function getModelForBean(OODBBean $bean) {
        $type = $bean->getMeta('type');
        try {
            if (self::$for instanceof Model) {
                $model = self::$for;
                self::$for = null;
            } elseif (isset(self::$models[$type])) {
                $class = self::$models[$type];
                $model = new $class();
            } else throw new ManjuException("Cannot find any model with type $type");
            $model->loadBean($bean);
            return $model;
        } catch (Throwable $exc) {
            $exc->getCode();
        }
        return parent::getModelForBean($bean);
    }

    /**
     * Dispense or loads a bean for a given model
     * @param Model $model
     * @param int|null $id
     */
    public static function dispenseFor(Model $model, int $id = null) {

        if (($type = $model->getMeta("type"))) {
            self::$for = $model;
            if (is_int($id)) ORM::load($type, $id);
            else ORM::dispense($type);
        }
    }

    /**
     * @param array<Model> $models
     * @throws ManjuException
     */
    public function __construct(array $models) {
        if ($logger = ORM::getPsrlogger()) $this->setLogger($logger);

        foreach ($models as $path) {
            autoloadDir($path);
        }
        $models = findClassesImplementing(Model::class);
        if (empty($models)) throw new ManjuException("Cannot locate any models extending " . Model::class);
        foreach ($models as $model) {
            $instance = new $model();
            self::addModel($instance);
        }
    }

    /**
     * Add a model to the list
     * @param Model $model
     */
    public static function addModel(Model $model) {
        self::buildMeta($model);
        if ($type = $model->getMeta("type")) self::$models[$type] = get_class($model);
    }

    /**
     * @param Model $model
     * @return void
     * @throws ManjuException
     */
    private static function buildMeta($model) {

        if (!($model instanceof Model)) {
            throw new ManjuException("Can only use trait " . __CLASS__ . "with class extending " . Model::class);
        }

        //loads converters
        $converters = &self::$converters;
        if (empty($converters)) {
            foreach (findClassesImplementing(Converter::class) as $class) {
                $converters[$class] = $class;
                foreach ($class::getTypes() as $keyword) {
                    $converters[$keyword] = $class;
                }
            }
        }

        //loads filters
        $filters = &self::$filters;
        if (empty($filters)) {
            foreach (findClassesImplementing(AnnotationFilter::class) as $class) {
                $filters[] = new $class();
            }
        }

        //Reads from cache
        $refl = new ReflectionClass($model);
        if ($pool = ORM::getCachePool()) {
            $fileinfo = new SplFileInfo($refl->getFileName());
            //use filemtime to parse new metadatas when model is modified
            $cachekey = md5($fileinfo->getMTime() . $fileinfo->getPathname());

            $item = $pool->getItem($cachekey);
            if ($item->isHit()) {
                self::$metadatas[get_class($model)] = $item->get();
                return;
            }
        }

        $meta = [
            //table name
            "type" => null,
            //property list
            "properties" => [],
            //properties data types
            "converters" => [],
            //defaults values (if property has a set value)
            "defaults" => [],
            // unique values
            "unique" => [],
            //not null values
            "required" => [],
            //enables created_at and updated_at
            "timestamps" => false
        ];

        //set type(table) (without annotations)
        if (
                isset($model::$type)
                and preg_match(Model::VALID_BEAN, $model::$type)
        ) {
            $meta["type"] = $model::$type;
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
                    and ! $prop->isPrivate() and ! $prop->isStatic()
            ) {
                $meta["properties"][] = $prop->name;
                $meta["converters"][$prop->name] = Text::class;

                $prop->setAccessible(true);
                if ($prop->getValue($model) !== null) $meta["defaults"][$prop->name] = $prop->getValue($model);
            }
        }

        //Reads annotations
        $parser = new Parser(ORM::getPsrlogger());
        if ($annotations = $parser->ParseAll($refl)) {
            //parse only extended Models, not base models annotations
            $annotations = array_filter($annotations, function ($ann) {
                return !in_array($ann->reflector->class ?? "", [Model::class, SimpleModel::class, Bun::class]);
            });
            /**
             * @var string
             * @converter string
             */
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
                unset($meta["converters"][$key], $meta["defaults"][$key]);
                foreach (["properties", "required", "unique"] as $metakey) {
                    $meta[$metakey] = array_filter($meta[$metakey], function ($val) use($key) { return $key !== $val; });
                }
            }
            unset($meta["ignore"]);
        }



        self::$metadatas[get_class($model)] = $meta;
        //save cache (if any)
        if ($pool instanceof CacheItemPoolInterface) {
            $item->set($meta);
            $item->expiresAfter(1 * day);
            $pool->save($item);
        }
    }

}
