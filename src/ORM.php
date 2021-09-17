<?php

declare(strict_types=1);

namespace NGSOFT\Manju;

/**
 * ORM Facade
 * Map all components
 */
final class ORM {

    const VERSION = '3.0.0';

    /** @var EntityManager */
    private static $entityManager;

    /**
     * Access Entity Manager
     *
     * @return EntityManager
     */
    public static function getEntityManager(): EntityManager {
        self::$entityManager = self::$entityManager ?? new EntityManager();
        return self::$entityManager;
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

}
