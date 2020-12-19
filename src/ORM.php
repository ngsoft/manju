<?php

namespace Manju;

use Manju\{
    Exceptions\ManjuException, Helpers\Cache, ORM\Bean
};
use Psr\{
    Cache\CacheItemPoolInterface, Container\ContainerInterface, Log\LoggerInterface
};
use RedBeanPHP\Facade;

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

    /** @var Connection[] */
    private static $connections;

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

        if ($container->has(LoggerInterface::class)) self::setLogger($container->get(LoggerInterface::class));
        if ($container->has(CacheItemPoolInterface::class)) self::setCachePool($container->get(CacheItemPoolInterface::class));
        if ($container->has(Connection::class)) {
            $connection = $container->get(Connection::class);
            self::addConnection($connection, true);
        }
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

    ///////////////////////////////// Connection Manager  /////////////////////////////////

    /**
     * Add a database connection to the ORM
     * @param Connection $connection
     * @param bool $selected Select the connection added.
     * @throws ManjuException
     * @suppress PhanTypeMismatchArgumentNullable
     */
    public static function addConnection(Connection $connection, bool $selected = false) {

        $name = $connection->getName();
        if (isset(Facade::$toolboxes[$name])) throw new ManjuException("Connection $name already exists.");
        if (!$connection->getDSN()) throw new ManjuException("No DSN provided for $name connection.");

        if (!in_array($connection, self::$connections)) {
            self::$connections[] = $connection;
        }



        Facade::addDatabase($name, $connection->getDSN(), $connection->getUsername(), $connection->getPassword());
        if ($selected == true) {
            Facade::selectDatabase($name, true);
            if (Facade:: testConnection() === false) throw new ManjuException("Cannot connect to database on connection $name");
        }
    }

    ///////////////////////////////// Redbean Proxy  /////////////////////////////////
    // load, dispense, save, tag, addTags, untag, tagged, taggedAll, countTaggedAll getRedBean
    // find, findOne, trash, store
}
