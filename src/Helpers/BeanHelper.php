<?php

declare(strict_types=1);

namespace Manju\Helpers;

use DateTime;
use Manju\{
    Converters\Date, Converters\Text, Exceptions\ManjuException, Exceptions\ValidationError, Filters\Ignore, Filters\Required,
    Filters\Timestamps, Filters\Type, Filters\Unique, Interfaces\AnnotationFilter, Interfaces\Converter, ORM, ORM\Model,
    Reflection\Parser
};
use Psr\Cache\{
    CacheItemInterface, CacheItemPoolInterface
};
use RedBeanPHP\{
    BeanHelper\SimpleFacadeBeanHelper, Facade, OODBBean, SimpleModel
};
use ReflectionClass,
    ReflectionException,
    SplFileInfo,
    Throwable;
use function Manju\{
    autoloadDir, findClassesImplementing, toSnake
};

final class BeanHelper extends SimpleFacadeBeanHelper {

    /** @var array<string,string> */
    protected static $models = [];

    /** @var array<string,string> */
    public static $converters = [];

    /** @var AnnotationFilter[] */
    public static $filters = [];

    /** @var array<string,ArrayObject> */
    public static $metadatas = [];

    /** @var string[] */
    public static $baseFilters = [
        Type::class,
        Ignore::class,
        Required::class,
        Timestamps::class,
        Unique::class,
    ];

    /** @var Model|null */
    protected static $for = null;

    /**
     * Loads Converters
     * @param string|null $path Path to class implementing Converter
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
     * @param string|null $path Path to class implementing AnnotationFilter
     */
    public static function loadFilters(string $path = null) {

        if (is_dir($path)) {
            autoloadDir($path);
            foreach (findClassesImplementing(AnnotationFilter::class) as $classname) {
                if (!isset(self::$filters[$classname])) self::$filters[$classname] = new $classname();
            }
        }
    }

    /**
     * Adds a Model Search Path
     * @param string ...$paths
     */
    public static function addSearchPath(string ...$paths) {

        foreach ($paths as $path) {
            $real = realpath($path);
            if (($real == false) or!is_dir($real)) throw new ManjuException("Invalid model search path $path.");
            autoloadDir($path);
        }
    }

    /**
     * Scans for classes implementing Model
     * and registers them
     */
    public static function scanForModels() {
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
        static $can_connect;
        if (!is_bool($can_connect)) $can_connect = ORM::canConnect();
        if ($can_connect == false) {
            if ($connection = ORM::getActiveConnection()) {
                throw new ManjuException('Cannot connect to database connection ' . $connection->getName());
            } else throw new ManjuException('No database connection has been established.');
        }
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

    /**
     * Loads Model Data from Bean
     * @param Model $model
     */
    public static function loadModel(Model $model) {
        if ($meta = $model->getMeta()) {
            $bean = $model->unbox();
            $refl = $meta->reflections;
            $id = &$bean->id;
            $refl['id']->setValue($model, $id);

            if ($meta->timestamps == true) {
                foreach (['created_at', 'updated_at'] as $prop) {
                    $beanValue = $bean->{$prop};
                    $value = Date::convertFromBean($beanValue);
                    $refl[$prop]->setValue($model, $value);
                }
            }
            foreach ($meta->converters as $prop => $converter) {
                $beanValue = $bean->{$prop};
                if ($beanValue !== null) {
                    $value = $converter::convertFromBean($beanValue);
                    $refl[$prop]->setValue($model, $value);
                }
            }
        }
    }

    /**
     * Validate Model Values using Converters
     * @param Model $model
     * @suppress PhanUndeclaredMethod
     */
    public static function validateModel(Model $model) {
        $classname = get_class($model);
        if ($meta = $model->getMeta()) {
            foreach ($meta->properties as $prop) {
                $rprop = $meta->reflections[$prop];
                $value = $rprop->getValue($model);
                $required = $meta->required->toArray();
                //check required
                if (
                        in_array($prop, $required)
                        and $value == null
                ) {
                    throw new ValidationError($classname . '::$' . $prop . " Cannot be NULL");
                }
                //check converter value
                $converter = $meta->converters[$prop] ?? null;
                if (
                        isset($converter)
                        and ($value != null)
                        and!$converter::isValid($value)
                ) {
                    throw new ValidationError(
                            $classname . '::$' . $prop . " Invalid Type " .
                            $converter::getTypes()[0] . " requested but " .
                            gettype($value) . " given."
                    );
                }
                // checks if a methode Model::validate($prop,$value) exists and run it
                if (
                        method_exists($model, 'validate')
                        and (false == $model->validate($prop, $value))
                ) {

                    throw new ValidationError($classname . "::validate($prop, ...) failed the validation test.");
                }
            }
        }
    }

    /**
     * Write Model datas to bean and checks for unique Properties
     * @param Model $model
     */
    public static function updateModel(Model $model) {

        $classname = get_class($model);
        $bean = $model->unbox();
        if ($meta = $model->getMeta()) {
            $refl = $meta->reflections;
            $unique = $meta->unique->toArray();
            //timestamps?
            if ($meta->timestamps == true) {
                $now = new DateTime();
                $created = $refl['created_at'];
                $updated = $refl['updated_at'];
                if ($created->getValue($model) == null) {
                    $bean->created_at = $now;
                    $created->setValue($model, $now);
                }
                $updated->setValue($model, $now);
                $bean->updated_at = $now;
            }

            foreach ($meta->converters as $prop => $converter) {
                if ($prop == 'id') continue;
                $value = $refl[$prop]->getValue($model);
                if ($value !== null) {
                    $beanValue = $converter::convertToBean($value);
                    $bean->{$prop} = $beanValue;
                    //checks unique value
                    if (
                            in_array($prop, $unique)
                            and!empty($value)
                    ) {
                        if ($entry = $classname::findOne(sprintf('%s = ?', $prop), [$beanValue])) {
                            if ($bean->id != $entry->id) {
                                throw new ValidationError($classname . '::$' . $prop . " unique value " . $value . " already exists.");
                            }
                        }
                    }
                } else $bean->{$prop} = null;
            }
        }
    }

    /**
     * Get all ReflectionProperties from Model
     * @param Model $model
     */
    private static function getReflections(Model $model) {
        $refl = new \ReflectionClass($model);
        $ignore = ['bean'];
        $result = [];
        try {

            do {
                foreach ($refl->getProperties() as $rprop) {
                    if (
                            !$rprop->isStatic()
                            and!$rprop->isPublic()
                            and!in_array($rprop->getName(), $ignore)
                    ) {
                        if (!isset($result[$rprop->getName()])) {
                            $rprop->setAccessible(true);
                            $result[$rprop->getName()] = $rprop;
                        }
                    }
                }
            } while ($refl = $refl->getParentClass());
        } catch (ReflectionException $e) { $e->getCode(); }

        return $result;
    }

    /**
     * Build Metadats for Model
     * @staticvar array $filters
     * @param Model $model
     */
    private static function buildMetaDatas(Model $model) {

        static $filters = [];

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
                $meta = $item->get();
                $meta['reflections'] = self::getReflections($model);
                self::$metadatas[$classname] = ArrayObject::from($meta);
                return;
            }
        }

        //Create metadata object
        $meta = [
            //builtin filters
            //property list
            "properties" => [],
            //properties data types
            "converters" => [],
            //table name
            "type" => null,
        ];

        if (empty($filters)) {
            foreach (self::$baseFilters as $filter) {
                $filters[$filter] = new $filter();
            }

            foreach (self::$filters as $filter => $instance) {
                if (!isset($filters[$filter])) {
                    $filters[$filter] = $instance;
                }
            }
        }

        if (!isset(self::$converters[Text::class])) {
            self::loadConverters();
        }

        $converters = &self::$converters;

        //creating initials meta values
        foreach ($filters as $filter) {
            $meta[$filter->getKey()] = $filter->getDefaultValue();
        }

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


        $properties = self::getReflections($model);

        //reads properties from model class
        foreach ($properties as $prop) {
            if (
                    ($prop->class !== Model::class )
                    and ( $prop->class !== SimpleModel::class )
                    and!$prop->isStatic()
                    and!$prop->isPublic()
            ) {
                $meta["properties"][] = $prop->name;
                $meta["converters"][$prop->name] = Text::class;

                // if typed default value
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

        //after processing
        foreach ($filters as $filter) {
            $filter->afterProcess($meta);
        }

        //save to cache
        if ($item instanceof CacheItemInterface) {
            $item->set($meta);
            $cache->save($item);
        }

        $meta['reflections'] = $properties;

        self::$metadatas[$classname] = ArrayObject::from($meta);
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
        if (!(Facade::getRedBean()->getBeanHelper() instanceof self)) {
            Facade::getRedBean()->setBeanHelper($this);
        }
    }

}
