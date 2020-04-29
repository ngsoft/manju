<?php

declare(strict_types=1);

namespace Manju;

use Manju\{
    Bun, Exceptions\ManjuException, Helpers\BeanHelper, Helpers\Cache, ORM\Bean
};
use Psr\{
    Cache\CacheItemPoolInterface, Container\ContainerInterface, Log\LoggerInterface
};
use RedBeanPHP\Facade;

define('REDBEAN_OODBBEAN_CLASS', Bean::class);

final class ORM extends Facade {

    const MANJU_VERSION = Bun::VERSION;

    /** @var ContainerInterface */
    private static $container;

    /** @var LoggerInterface */
    private static $psrlogger;

    /** @var string */
    private static $loglevel = "debug";

    /** @var CacheItemPoolInterface */
    private static $cache;

    /** @var int */
    private static $ttl = 60 * 60 * 24; // 1 day (cache will detects models changes)

    /**
     * Starts Manju ORM
     * @staticvar boolean $started
     * @param string ...$pathtomodels Directories where to find models extending Manju\ORM\Model
     * @throws ManjuException
     */
    public static function start(string ...$pathtomodels) {
        static $started;
        if ($started !== true) {
            if (empty(self::$toolboxes)) throw new ManjuException("Cannot start ORM, no connections set.");
            if (self::testConnection() === false) throw new ManjuException("Cannot start ORM, cannot connect to the database.");
            autoloadDir(__DIR__ . '/Converters'); autoloadDir(__DIR__ . '/Filters');
            $helper = new BeanHelper();
            self::getRedBean()->setBeanHelper($helper);
            $started = true;
        }
        if (count($pathtomodels) > 0) self::addModelPath(...$pathtomodels);
    }

    /**
     * Adds Path to Modelss
     * @param string ...$path Directories where to find models extending Manju\ORM\Model
     */
    public static function addModelPath(string ...$path) {
        BeanHelper::addModelPath(...$pathtomodels);
    }

    /**
     * @return ContainerInterface|null
     */
    public static function getContainer(): ?ContainerInterface {
        return self::$container;
    }

    /**
     * Adds a PSR Container (Autoconf if logger and cache pool are set up)
     * @param ContainerInterface $container
     */
    public static function setContainer(ContainerInterface $container) {
        self::$container = $container;
        if ($container->has(LoggerInterface::class)) self::setLogger($container->get(LoggerInterface::class));
        if ($container->has(CacheItemPoolInterface::class)) self::setCachePool($container->get(CacheItemPoolInterface::class));
        if ($container->has(Connection::class)) {
            $connection = $container->get(Connection::class);
            self::addConnection($connection, true);
        }
    }

    /**
     *  @return LoggerInterface|null
     */
    public static function getLogger(): ?LoggerInterface {
        return self::$psrlogger;
    }

    /**
     * @return string
     */
    public static function getLoglevel(): string {
        return self::$loglevel;
    }

    /**
     * Add a database connection to the ORM
     * @param Connection $connection
     * @param bool $select Select the connection added.
     * @throws ManjuException
     */
    public static function addConnection(Connection $connection, bool $select = false) {
        $name = $connection->getName();
        if (isset(self::$toolboxes[$name])) throw new ManjuException("Connection $name already exists.");

        self::addDatabase(
                $connection->getName(),
                $connection->getDSN(),
                $connection->getUsername(),
                $connection->getPassword(),
                $connection->getFrozen()
        );

        if ($select === true) {
            self::selectDatabase($name, true);
            if (self::testConnection() === false) throw new ManjuException("Cannot connect to database.");
        }
    }

    /**
     * Add a PSR Logger
     * @param LoggerInterface $psrlogger
     * @param string|null $loglevel
     */
    public static function setLogger(LoggerInterface $psrlogger, string $loglevel = null) {
        self::$psrlogger = $psrlogger;
        if ($loglevel !== null) self::$loglevel = $loglevel;
    }

    /**
     * @return CacheItemPoolInterface|null
     */
    public static function getCachePool(): ?CacheItemPoolInterface {
        return self::$cache;
    }

    /**
     * Adds a PSR Cache Pool
     * @param CacheItemPoolInterface $cache
     * @param int $ttl
     */
    public static function setCachePool(CacheItemPoolInterface $cache, int $ttl = null) {
        if ($ttl !== null) self::$ttl = $ttl;
        self::$cache = new Cache($cache, self::$ttl);
    }

}
