<?php

namespace Manju\Traits;

use Manju\Exceptions\ManjuException;
use Manju\ORM;
use Manju\ORM\Model;
use Psr\Cache\CacheItemPoolInterface;
use RedBeanPHP\SimpleModel;
use ReflectionClass;
use SplFileInfo;
use function NGSOFT\Tools\toSnake;

trait Metadata {

    /** @var array */
    protected $metadata = [
        "type" => null,
        "properties" => ["id"],
        "converters" => [],
        "uniques" => []
    ];
    protected $converters = [];

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

        $refl = new ReflectionClass($this);
        if ($pool = ORM::getCachePool()) {
            $fileinfo = new SplFileInfo($refl->getFileName());
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
            }
        }







        print_r($this->metadata);


        if ($pool instanceof CacheItemPoolInterface) {
            $item->set($this->metadata);
            $pool->save($item);
        }
    }

}
