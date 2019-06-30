<?php

namespace Manju\Traits;

use Manju\Converters\Date;
use Manju\Converters\Number;
use Manju\Converters\Text;
use Manju\Exceptions\ManjuException;
use Manju\Interfaces\Converter;
use Manju\ORM;
use Manju\ORM\Model;
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
        "properties" => ["id", "created_at", "updated_at"],
        //properties data types
        "converters" => [
            "id" => Number::class,
            "created_at" => Date::class,
            "updated_at" => Date::class,
        ],
        // unique values
        "uniques" => [],
        //not null values
        "required" => [],
        //enables created and updated
        "timestamps" => false
    ];

    /** @var array<string,Converter> */
    protected static $converters = [];

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



        //set type (without annotations)
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
            }
        }







        print_r($this->metadata);


        if ($pool instanceof CacheItemPoolInterface) {
            $item->set($this->metadata);
            $item->expiresAfter(1 * day);
            $pool->save($item);
        }
    }

}
