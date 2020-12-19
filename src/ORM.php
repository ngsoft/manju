<?php

namespace Manju;

use Manju\{
    Helpers\Cache, ORM\Bean
};
use Psr\{
    Cache\CacheItemPoolInterface, Container\ContainerInterface, Log\LoggerInterface
};

define('REDBEAN_OODBBEAN_CLASS', Bean::class);

final class ORM {

    const VERSION = '3.0';
    const LOGLEVEL = 'debug';
    const CACHE_TTL = 60 * 60 * 24;

    /** @var ContainerInterface */
    private static $container;

    /** @var LoggerInterface */
    private static $logger;

    /** @var CacheItemPoolInterface */
    private static $cache;

    /** @return ContainerInterface|null */
    public static function getContainer(): ?ContainerInterface {
        return self::$container;
    }

    /** @return LoggerInterface|null */
    public static function getLogger(): ?LoggerInterface {
        return self::$logger;
    }

    /** @return CacheItemPoolInterface|null */
    public static function getCachePool(): ?CacheItemPoolInterface {
        return self::$cache;
    }

    /**
     * Adds a PSR Container (Autoconf if logger and cache pool are set up)
     * @param ContainerInterface $container
     */
    public static function setContainer(ContainerInterface $container) {
        self::$container = $container;
    }

    /**
     * Add a PSR Logger
     * @param LoggerInterface $psrlogger
     * @param string|null $loglevel
     */
    public static function setLogger(LoggerInterface $logger) {
        self::$logger = $logger;
    }

    /**
     * Adds a PSR Cache Pool
     * @param CacheItemPoolInterface $cache
     * @param int $ttl
     */
    public static function setCachePool(CacheItemPoolInterface $cache, int $ttl = self::CACHE_TTL) {
        self::$cache = new Cache($cache, $ttl);
    }

    ///////////////////////////////// Redbean Proxy  /////////////////////////////////
    // load, dispense, save, tag, addTags, untag, tagged, taggedAll, countTaggedAll getRedBean
    // find, findOne, trash, store
}
