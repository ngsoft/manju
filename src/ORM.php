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

    const VERSION = '3.0.beta1';
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
        if (!in_array($connection, self::$connections)) {
            self::$connections[$name] = $connection;
        }
        if (isset(Facade::$toolboxes[$name])) throw new ManjuException("Connection $name already exists.");
        if ($connection->addToRedBean() and $selected) $connection->setActive();
    }

    ///////////////////////////////// Model Manager  /////////////////////////////////

    /**
     * Adds Path to Classes implementing Model
     * @param string ...$paths
     */
    public static function addModelPath(string ...$paths) {
        BeanHelper::addSearchPath(...$paths);
    }

    /**
     * Adds a single Model
     * @param Model $model
     */
    public function addModel(Model $model) {
        return BeanHelper::addModel($model);
    }

    ///////////////////////////////// Initialisation  /////////////////////////////////

    /**
     * Starts the ORM
     * @staticvar type $started
     * @param string|null $searchpath Path to Models
     * @param Connection|null $connection Connection to use
     */
    public static function start(?string $searchpath = null, ?Connection $connection = null) {
        static $started;
        if ($started != true) {
            if ($connection instanceof Connection) self::addConnection($connection, true);
            elseif (count(self::$connections) == 0) throw new ManjuException("Cannot start ORM, No connections defined");
            //initialize helper
            (new BeanHelper());
            $started = true;
        }
        if (is_string($searchpath)) self::addModelPath($searchpath);
    }

}
