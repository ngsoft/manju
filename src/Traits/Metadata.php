<?php

namespace Manju\Traits;

use Manju\Bun;
use Manju\Converters\Date;
use Manju\Converters\Number;
use Manju\Converters\Text;
use Manju\Exceptions\ManjuException;
use Manju\Interfaces\AnnotationFilter;
use Manju\Interfaces\Converter;
use Manju\ORM;
use Manju\ORM\Model;
use NGSOFT\Tools\Reflection\Parser;
use Psr\Cache\CacheItemPoolInterface;
use RedBeanPHP\SimpleModel;
use ReflectionClass;
use SplFileInfo;
use const day;
use function NGSOFT\Tools\findClassesImplementing;
use function NGSOFT\Tools\toSnake;

trait Metadata {

    /** @var array<string,mixed> Table Schema */
    protected $metadata = [
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
        //access right
        "access" => [],
        //relations
        "relations" => [],
        //enables created_at and updated_at
        "timestamps" => false
    ];

    /** @var array<string,Converter> */
    protected static $converters = [];

    /** @var array<AnnotationFilter> */
    protected static $filters = [];

    /**
     * Get Model Metadatas
     * @param string|null $key
     * @return mixed
     */
    public function getMeta(string $key = null) {
        if (!isset($this->metadata["type"])) $this->buildMetas();

        if ($key === null) return $this->metadata;
        return $this->metadata[$key] ?? null;
    }

    private function buildMetas() {

        if (!($this instanceof Model)) {
            throw new ManjuException("Can only use trait " . __CLASS__ . "with class extending " . Model::class);
        }

        //loads converters
        $converters = &self::$converters;
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
        $filters = &self::$filters;
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
                $this->metadata = $item->get();
                return;
            }
        }

        //set type(table) (without annotations)
        if (
                isset(static::$type)
                and preg_match(Model::VALID_BEAN, static::$type)
        ) {
            $this->metadata["type"] = static::$type;
        } else {
            $short = $refl->getShortName();
            $snake = preg_replace('/_?(model|entity)_?/', "", toSnake($short));
            $parts = explode("_", $snake);
            $type = array_pop($parts);
            if (preg_match(Model::VALID_BEAN, $type)) $this->metadata["type"] = $type;
        }


        //reads properties from model class
        foreach ($refl->getProperties() as $prop) {
            if (
                    ($prop->class !== Model::class )
                    and ( $prop->class !== SimpleModel::class )
                    and $prop->isProtected()
                    and ! $prop->isPrivate() and ! $prop->isStatic()
            ) {
                $this->metadata["properties"][] = $prop->name;
                $this->metadata["converters"][$prop->name] = Text::class;
                if ($this->{$prop->name} !== null) $this->metadata["defaults"][$prop->name] = $this->{$prop->name};
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
                if ($annotation->annotationType === "PROPERTY" && ($annotation->tag === "var" || $annotation->tag === "converter") && in_array($annotation->attributeName, $this->metadata["properties"])) {
                    if (is_string($annotation->value)) {
                        $value = preg_replace('/^([a-zA-Z]+).*?$/', "$1", $annotation->value);
                        if (isset($converters[$value])) {
                            $this->metadata["converters"][$annotation->attributeName] = get_class($converters[$value]);
                        } else print_r($annotation);
                    }
                }
            }
            foreach ($filters as $filter) {
                $filter->process($annotations, $this->metadata);
            }
        }






        //add id
        $this->metadata["properties"][] = "id";
        $this->metadata["converters"]["id"] = Number::class;
        //add timestamps
        if ($this->metadata["timestamps"] === true) {
            $this->metadata["properties"] = array_merge($this->metadata["properties"], ["created_at", "updated_at"]);
            $this->metadata["converters"] = array_merge($this->metadata["converters"], [
                "created_at" => Date::class,
                "updated_at" => Date::class
            ]);
        }
        //set access rights
        foreach ($this->metadata["properties"] as $prop) {
            if (!array_key_exists($prop, $this->metadata["access"])) $this->metadata["access"][$prop] = Model::AUTO_PROPERTY_NONE;
        }
        foreach (array_keys($this->metadata["relations"]) as $prop) {
            if (!array_key_exists($prop, $this->metadata["access"])) $this->metadata["access"][$prop] = Model::AUTO_PROPERTY_NONE;
        }

        print_r($this->metadata);

        //save cache (if any)
        if ($pool instanceof CacheItemPoolInterface) {
            $item->set($this->metadata);
            $item->expiresAfter(1 * day);
            $pool->save($item);
        }
    }

}
