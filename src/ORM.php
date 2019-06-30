<?php

namespace Manju;

use Manju\Exceptions\ManjuException;
use Manju\Helpers\Bean;
use Manju\Helpers\BeanHelper;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use RedBeanPHP\Facade;
use RedBeanPHP\RedException;
use function NGSOFT\Tools\autoloadDir;

define('REDBEAN_OODBBEAN_CLASS', Bean::class);

class ORM extends Facade {

    const VERSION = Bun::VERSION;

    /** @var LoggerInterface */
    protected static $psrlogger;

    /** var array<string,mixed> */
    protected static $config = [
        "models" => [],
        "timezone" => "Europe/Paris",
        "connection" => "default",
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
            print_r(self::$config);
            if (empty(static::$config["models"])) throw new ManjuException("No model path set.");
            if (!isset(self::$toolboxes["default"])) {
                foreach (self::$config["db"] as $connection => $params) {
                    if ($connection === "default") {
                        self::setup($params["dsn"] ?? null, $params["username"] ?? null, $params["password"] ?? null, $params["frozen"] ?? false);
                    } else {
                        self::addDatabase($connection, $params["dsn"], $params["username"] ?? null, $params["password"] ?? null, $params["frozen"] ?? false);
                    }
                }
                if (self::$config["connection"] !== "default") self::selectDatabase(self::$config["connection"]);
            }
            if (!self::testConnection()) throw new RedException("Cannot connect to the database, please setup your connection.");
            date_default_timezone_set(self::$config["timezone"]);

            //preload converters
            autoloadDir(__DIR__ . '/Converters');

            $helper = new BeanHelper(self::$config["models"]);
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
