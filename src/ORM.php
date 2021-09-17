<?php

declare(strict_types=1);

namespace NGSOFT\Manju;

use Psr\{
    Cache\CacheItemPoolInterface, EventDispatcher\EventDispatcherInterface, EventDispatcher\ListenerProviderInterface, Log\LoggerInterface
};

/**
 * ORM Facade
 * Map all components
 */
final class ORM {

    const VERSION = '3.0.0';

    /** @var EntityManager */
    private static $entityManager;

    ////////////////////////////   Getters/Setters   ////////////////////////////

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

}
