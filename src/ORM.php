<?php

namespace Manju;

use Manju\Exceptions\ManjuException;
use Manju\Helpers\Bean;
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
        ]
    ];
    protected static $started = false;

    public static function configure(array $config) {
        foreach (self::$config as $k => $v) {
            if (array_key_exists($k, $config) and gettype($v) === gettype(self::$config[$k])) {
                self::$config[$k] = $v;
            }
        }
    }

    public static function start() {
        if (!self::$started) {
            if (empty(static::$config["models"])) throw new ManjuException("No model path set.");
            if (!isset(self::$toolboxes["default"])) {
                foreach (self::$config[db] as $connection => $params) {
                    if ($connection === "default") {
                        self::setup($params["dsn"] ?? null, $params["username"] ?? null, $params["password"] ?? null, $params["frozen"] ?? false);
                    } else {
                        self::addDatabase($connection, $params["dsn"], $params["username"] ?? null, $params["password"] ?? null, $params["frozen"] ?? false);
                    }
                }
                if (self::$config["connection"] !== "default") self::selectDatabase(self::$config["connection"]);
            }
            if (!self::testConnection()) throw new RedException("Cannot connect to the database, please setup your connection.");

            foreach (self::$config["models"] as $path) {
                autoloadDir($path);
            }
        }
    }

    public static function getPsrlogger(): LoggerInterface {
        return self::$psrlogger;
    }

    public static function setPsrlogger(LoggerInterface $psrlogger) {
        self::$psrlogger = $psrlogger;
    }

}
