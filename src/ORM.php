<?php

declare(strict_types=1);

namespace Manju;

use Manju\{
    Bun, Exceptions\ManjuException, Helpers\Cache, ORM\Bean
};
use Psr\{
    Cache\CacheItemPoolInterface, Log\LoggerInterface
};
use RedBeanPHP\Facade;

define('REDBEAN_OODBBEAN_CLASS', Bean::class);

final class ORM extends Facade {

    const MANJU_VERSION = Bun::VERSION;

    /** @var LoggerInterface */
    private static $psrlogger;

    /** @var string */
    private static $loglevel = "debug";

    /** @var CacheItemPoolInterface */
    private static $cache;

    /** @var int */
    private static $ttl = 60 * 60 * 24;

    /** @var array<string,Connection> */
    private static $connections = [];

    public static function addDatabase($key, $dsn, $user = NULL, $pass = NULL, $frozen = FALSE, $partialBeans = FALSE) {
        $connection = new Connection([
            "dsn" => $dsn,
            "username" => $user,
            "password" => $pass
                ], $frozen, $key);
        $this->addConnection($connection);

        parent::addDatabase($key, $dsn, $user, $pass, $frozen, $partialBeans);
    }

    /**
     *  @return LoggerInterface|null
     */
    public static function getLogger(): ?LoggerInterface {
        return self::$psrlogger;
    }

    /**
     *
     * @return string
     */
    public static function getLoglevel(): string {
        return self::$loglevel;
    }

    /**
     * Add a database connection to the ORM
     * @param Connection $connection
     * @throws ManjuException
     */
    public static function addConnection(Connection $connection) {
        $name = $connection->getName();
        if (isset(self::$connections[$name])) throw new ManjuException("Connection $name already exists.");
    }

    /**
     *
     * @param LoggerInterface $psrlogger
     * @param string|null $loglevel
     */
    public static function setLogger(LoggerInterface $psrlogger, string $loglevel = null) {
        self::$psrlogger = $psrlogger;
        if ($loglevel !== null) self::$loglevel = $loglevel;
    }

    /**
     *
     * @return CacheItemPoolInterface|null
     */
    public static function getCachePool(): ?CacheItemPoolInterface {
        return self::$cache;
    }

    /**
     *
     * @param CacheItemPoolInterface $cache
     * @param int $ttl
     */
    public static function setCachePool(CacheItemPoolInterface $cache, int $ttl = null) {
        if ($ttl !== null) self::$ttl = $ttl;
        self::$cache = new Cache($cache, $ttl);
    }

}
