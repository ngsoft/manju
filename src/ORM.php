<?php

declare(strict_types=1);

namespace Manju;

use Manju\{
    Exceptions\ManjuException, Helpers\BeanHelper, ORM\Bean
};
use Psr\{
    Cache\CacheItemPoolInterface, Log\LoggerInterface
};
use RedBeanPHP\{
    Facade, RedException
};
use function Manju\autoloadDir;

define('REDBEAN_OODBBEAN_CLASS', Bean::class);

class ORM extends Facade {

    const VERSION = Bun::VERSION;

    /** @var LoggerInterface */
    protected static $psrlogger;

    /** var array<string,mixed> */
    protected static $config = [
        "metacachettl" => 60 * 60 * 24,
        "models" => [],
        "connection" => "default",
        "loglevel" => "debug",
        "db" => [
            "default" => [
                "dsn" => null,
                "username" => null,
                "password" => null,
                "frozen" => false
            ]
        ],
        LoggerInterface::class => null,
        CacheItemPoolInterface::class => null
    ];
    protected static $started = false;

    /**
     * Configure Manju ORM
     * @param array<string,mixed> $config
     */
    public static function configure(array $config) {
        foreach (self::$config as $k => $v) {
            if (array_key_exists($k, $config) and gettype($v) === gettype(self::$config[$k])) {
                self::$config[$k] = $config[$k];
            }
        }
        if (isset($config[LoggerInterface::class])) self::setPsrlogger($config[LoggerInterface::class]);
        if (isset($config[CacheItemPoolInterface::class])) self::setCachePool($config[CacheItemPoolInterface::class]);
    }

    /**
     * Starts Manju ORM
     * @param array<string,mixed> $config
     * @throws ManjuException
     * @throws RedException
     */
    public static function start(array $config = []) {
        if (!self::$started) {
            if (count($config)) self::configure($config);
            if (empty(static::$config["models"])) throw new ManjuException("No model path set.");
            if (!isset(self::$toolboxes["default"])) {
                foreach (self::$config["db"] as $connection => $params) {
                    if ($connection === "default") {
                        self::setup(
                                is_string($params["dsn"]) ? $params["dsn"] : null,
                                is_string($params["username"]) ? $params["username"] : null,
                                is_string($params["password"]) ? $params["password"] : null,
                                is_bool($params["frozen"]) ? $params["frozen"] : false
                        );
                    } else {
                        self::addDatabase(
                                $connection,
                                (string) $params["dsn"],
                                $params["username"] ?? "",
                                $params["password"] ?? null,
                                $params["frozen"] ?? false
                        );
                    }
                }
                if (self::$config["connection"] !== "default") self::selectDatabase(self::$config["connection"]);
            }
            if (!self::testConnection()) throw new RedException("Cannot connect to the database, please setup your connection.");


            //preload converters
            autoloadDir(__DIR__ . '/Converters');
            //preload Filters
            autoloadDir(__DIR__ . "/Filters");

            $helper = new BeanHelper(self::$config["models"], self::$config["metacachettl"]);
            self::getRedBean()->setBeanHelper($helper);

            self::$started = true;
        }
    }

    /**
     * Get current declared logger
     * @return LoggerInterface|null
     */
    public static function getPsrlogger(): ?LoggerInterface {
        return self::$config[LoggerInterface::class];
    }

    /**
     * Set the logger
     * @param LoggerInterface $psrlogger
     */
    public static function setPsrlogger(LoggerInterface $psrlogger) {
        self::$config[LoggerInterface::class] = $psrlogger;
    }

    /**
     * get current cache pool
     * @return CacheItemPoolInterface|null
     */
    public static function getCachePool(): ?CacheItemPoolInterface {
        return self::$config[CacheItemPoolInterface::class];
    }

    /**
     * Set the cache pool
     * @param CacheItemPoolInterface $pool
     */
    public static function setCachePool(CacheItemPoolInterface $pool) {
        self::$config[CacheItemPoolInterface::class] = $pool;
    }

}
