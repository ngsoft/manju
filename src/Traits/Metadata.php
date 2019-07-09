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

    /** @var array<string,mixed> Table Schema */
    protected $metadata = [
    ];

    /**
     * Get Model Metadatas
     * @param string|null $key
     * @return mixed
     */
    public function getMeta(string $key = null) {
        $meta = &BeanHelper::$metadatas;
        if (!isset($meta[get_class($this)])) $this->buildMetas();

        if ($key === null) return $meta[get_class($this)];
        return $meta[get_class($this)][$key] ?? null;
    }

    /**
     * @return void
     * @throws ManjuException
     */
    private function buildMetas() {

        if (!($this instanceof Model)) {
            throw new ManjuException("Can only use trait " . __CLASS__ . "with class extending " . Model::class);
        }

        //loads converters
        $converters = &BeanHelper::$converters;
        if (empty($converters)) {
            foreach (findClassesImplementing(Converter::class) as $class) {
                $conv = new $class();
                $converters[$class] = $conv;
                foreach ($conv->getTypes() as $keyword) {
                    $converters[$keyword] = $conv;
                }
            }
        }

        //loads filters
        $filters = &BeanHelper::$filters;
        if (empty($filters)) {
            foreach (findClassesImplementing(AnnotationFilter::class) as $class) {
                $filters[] = new $class();
            }
        }

        //Reads from cache
        $refl = new ReflectionClass($this);
        if ($pool = ORM::getCachePool()) {
            $fileinfo = new SplFileInfo($refl->getFileName());
            //use filemtime to parse new metadatas when model is modified
            $cachekey = md5($fileinfo->getMTime() . $fileinfo->getPathname());

            $item = $pool->getItem($cachekey);
            if ($item->isHit()) {
                BeanHelper::$metadatas[get_class($this)] = $item->get();
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
            //relations
            "relations" => [],
            //enables created_at and updated_at
            "timestamps" => false
        ];

        //set type(table) (without annotations)
        if (
                isset(static::$type)
                and preg_match(Model::VALID_BEAN, static::$type)
        ) {
            $meta["type"] = static::$type;
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
                if ($this->{$prop->name} !== null) $meta["defaults"][$prop->name] = $this->{$prop->name};
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
                        $value = preg_replace('/^([a-zA-Z]+).*?$/', "$1", $annotation->value);
                        if (isset($converters[$value])) {
                            $meta["converters"][$annotation->attributeName] = get_class($converters[$value]);
                        } else print_r($annotation);
                    }
                }
            }
            foreach ($filters as $filter) {
                $filter->process($annotations, $meta);
            }
        }


        //add id
        $meta["properties"][] = "id";
        $meta["converters"]["id"] = Number::class;
        //add timestamps
        if ($meta["timestamps"] === true) {
            $meta["properties"] = array_merge($meta["properties"], ["created_at", "updated_at"]);
            $meta["converters"] = array_merge($meta["converters"], [
                "created_at" => Date::class,
                "updated_at" => Date::class
            ]);
        }

        BeanHelper::$metadatas[get_class($this)] = $meta;
        //save cache (if any)
        if ($pool instanceof CacheItemPoolInterface) {
            $item->set($meta);
            $item->expiresAfter(1 * day);
            $pool->save($item);
        }
    }

}
