<?php

declare(strict_types=1);

namespace Manju;

use Manju\{
    Exceptions\ManjuException, Helpers\BeanHelper, Helpers\Cache, ORM\Bean, ORM\Model
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

    /** @var ContainerInterface|null */
    private static $container;

    /** @var LoggerInterface|null */
    private static $logger;

    /** @var CacheItemPoolInterface|null */
    private static $cache;

    /** @var array<string,Connection> */
    private static $connections = [];

    /** @var bool */
    private static $started = false;

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
     * @param LoggerInterface $logger
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
        if (!in_array($connection, self::$connections)) self::$connections[$name] = $connection;
        if (isset(Facade::$toolboxes[$name])) throw new ManjuException("Connection $name already exists.");
        if ($connection->addToRedBean() and $selected) $connection->setActive();
    }

    /**
     * Get Currently active connection
     * @return Connection|null
     */
    public static function getActiveConnection(): ?Connection {
        if (
                !empty(Facade::$currentDB)
                and isset(self::$connections[Facade::$currentDB])
        ) {
            return self::$connections[Facade::$currentDB];
        }
        return null;
    }

    /**
     * Checks if RedBean has an active connection and can connect to it
     * @return bool
     */
    public static function canConnect(): bool {
        if ($connection = self::getActiveConnection()) {
            return $connection->testConnection();
        }
        return false;
    }

    ///////////////////////////////// Model Manager  /////////////////////////////////

    /**
     * Adds Path to Classes implementing Model
     * @param string ...$paths
     */
    public static function addModelPath(string ...$paths) {
        BeanHelper::addSearchPath(...$paths);
        if (self::$started == true) BeanHelper::scanForModels();
    }

    /**
     * Adds a single Model
     * @param Model $model
     */
    public function addModel(Model $model) {
        if (self::$started == false) {
            throw new ManjuException('Cannot add Model: ORM not started.');
        }
        BeanHelper::addModel($model);
    }

    /**
     * Initialize Bean Helper
     */
    private static function initializeBeanHelper() {
        if (!(Facade::getRedBean()->getBeanHelper() instanceof BeanHelper)) {
            (new BeanHelper());
        }
    }

    ///////////////////////////////// Initialisation  /////////////////////////////////

    /**
     * Starts the ORM
     * @param string|null $searchpath Path to Models
     * @param Connection|null $connection Connection to use
     */
    public static function start(?string $searchpath = null, ?Connection $connection = null) {

        if (self::$started != true) {
            if ($connection instanceof Connection) self::addConnection($connection, true);
            elseif (count(self::$connections) == 0) throw new ManjuException("Cannot start ORM, No connections defined");
            self::initializeBeanHelper();
            if (is_string($searchpath)) self::addModelPath($searchpath);
            BeanHelper::scanForModels();
            self:: $started = true;
        }
    }

}
