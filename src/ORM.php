<?php

declare(strict_types=1);

namespace NGSOFT\Manju;

use NGSOFT\Manju\Connection\{
    DSN, SQLite
};
use Psr\{
    Cache\CacheItemPoolInterface, EventDispatcher\EventDispatcherInterface, EventDispatcher\ListenerProviderInterface, Log\LoggerInterface
};
use RedBeanPHP\ToolBox;

/**
 * ORM Facade
 * Map all components
 */
final class ORM {

    const VERSION = '3.0.0';

    /** @var EntityManager */
    private static $entityManager;

    /** @var DSN */
    private static $connection;

    ////////////////////////////   Getters   ////////////////////////////

    /**
     * Access Entity Manager
     *
     * @return EntityManager
     */
    public static function getEntityManager(): EntityManager {
        self::$entityManager = self::$entityManager ?? new EntityManager();
        return self::$entityManager;
    }

    /** @return LoggerInterface */
    public static function getLogger(): LoggerInterface {
        return self::getEntityManager()->getLogger();
    }

    /** @return CacheItemPoolInterface */
    public static function getCachePool(): CacheItemPoolInterface {
        return self::getEntityManager()->getCachePool();
    }

    /** @return ?ListenerProviderInterface */
    public function getEventListener(): ?ListenerProviderInterface {
        return self::$entityManager->getEventListener();
    }

    /** @return EventDispatcherInterface */
    public function getEventDispatcher(): EventDispatcherInterface {
        return self::$entityManager->getEventDispatcher();
    }

    /** @return DSN */
    public static function getConnection(): DSN {
        if (is_null(self::$connection)) self::setConnection(new SQLite());
        return self::$connection;
    }

    /** @return ?ToolBox */
    public static function getToolBox(): ?ToolBox {
        return self::getConnection()->getToolbox();
    }

    ////////////////////////////   Setters   ////////////////////////////

    /**
     * Set configured Entity Manager
     *
     * @param EntityManager $entityManager
     * @return void
     */
    public static function setEntityManager(EntityManager $entityManager): void {
        self::$entityManager = $entityManager;
    }

    /** @param LoggerInterface $logger */
    public static function setLogger(LoggerInterface $logger) {
        self::getEntityManager()->setLogger($logger);
    }

    /** @param CacheItemPoolInterface $cachePool */
    public static function setCachePool(CacheItemPoolInterface $cachePool) {
        self::$entityManager->setCachePool($cachePool);
    }

    /** @param EventDispatcherInterface $eventDispatcher */
    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher) {
        self::getEntityManager()->setEventDispatcher($eventDispatcher);
    }

    /**
     * Set Active Connection
     * @param DSN $connection
     */
    public static function setConnection(DSN $connection) {
        self::$connection = $connection;
        $connection->setActive();
    }

}
