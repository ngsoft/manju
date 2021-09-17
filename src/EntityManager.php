<?php

declare(strict_types=1);

namespace NGSOFT\Manju;

use Fig\Cache\Memory\MemoryPool;
use NGSOFT\{
    Events\EventDispatcher, Manju\Events\FuseEvent
};
use Psr\{
    Cache\CacheItemPoolInterface, EventDispatcher\EventDispatcherInterface, EventDispatcher\ListenerProviderInterface, Log\LoggerInterface, Log\NullLogger
};
use Symfony\Component\EventDispatcher\EventDispatcherInterface as SymfonyEventDispatcherInterface;

final class EntityManager {

    /** @var ?ListenerProviderInterface */
    private $eventListener;

    /** @var ?EventDispatcherInterface */
    private $eventDispatcher;

    /** @var ?CacheItemPoolInterface */
    private $cachePool;

    /** @var ?LoggerInterface */
    private $logger;

    ////////////////////////////   Initialisation   ////////////////////////////

    /**
     * @param ?EventDispatcherInterface $eventDispatcher
     * @param ?ListenerProviderInterface $eventListener
     * @param ?LoggerInterface $logger
     * @param ?CacheItemPoolInterface $cachePool
     */
    public function __construct(
            EventDispatcherInterface $eventDispatcher = null,
            ListenerProviderInterface $eventListener = null,
            LoggerInterface $logger = null,
            CacheItemPoolInterface $cachePool = null
    ) {

        $this->eventDispatcher = $eventDispatcher;
        $this->eventListener = $eventListener;
        $this->logger = $logger;
        $this->cachePool = $cachePool;

        if (is_null(self::$instance)) self::$instance = $this;
    }

    /**
     * Creates a new instance
     *
     * @param ?EventDispatcherInterface $eventDispatcher
     * @param ?ListenerProviderInterface $eventListener
     * @param ?LoggerInterface $logger
     * @param ?CacheItemPoolInterface $cachePool
     * @return static
     */
    public static function create(
            EventDispatcherInterface $eventDispatcher = null,
            ListenerProviderInterface $eventListener = null,
            LoggerInterface $logger = null,
            CacheItemPoolInterface $cachePool = null
    ): self {
        return new static($eventDispatcher, $eventListener, $logger, $cachePool);
    }

    ////////////////////////////   Getters/Setters   ////////////////////////////

    /**
     * Set Event Dispatcher
     * @param EventDispatcherInterface $eventDispatcher
     * @return void
     */
    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher): void {
        $this->eventDispatcher = $eventDispatcher;
        if (is_null($this->eventListener)) {
            //symphony event dispatcher compatibiliy
            $this->eventListener = $eventDispatcher instanceof SymfonyEventDispatcherInterface ? $eventDispatcher : new EventListener();
            // register Listeners
            foreach (FuseEvent::FUSE_EVENTS as $eventType) {
                $this->eventListener->addListener($eventType, function (FuseEvent $event) {
                    $event->onEvent();
                }, 10);
            }
        }
    }

    /**  @return ListenerProviderInterface|null */
    public function getEventListener(): ?ListenerProviderInterface {
        return $this->eventListener;
    }

    /** @return EventDispatcherInterface */
    public function getEventDispatcher(): EventDispatcherInterface {
        if (is_null($this->eventDispatcher)) {
            $this->setEventDispatcher(new EventDispatcher());
        }
        return $this->eventDispatcher;
    }

    /**
     * Get Cache Pool
     * @return CacheItemPoolInterface
     */
    public function getCachePool(): CacheItemPoolInterface {

        if (is_null($this->cachePool)) {
            $this->setCachePool(new MemoryPool());
        }

        return $this->cachePool;
    }

    /**
     * Set The CachePool
     * @param CacheItemPoolInterface $cachePool
     * @return void
     */
    public function setCachePool(CacheItemPoolInterface $cachePool): void {
        $this->cachePool = $cachePool;
    }

    /**
     * Get The Logger
     *
     * @return LoggerInterface
     */
    public function getLogger(): LoggerInterface {
        if (is_null($this->logger)) {
            $this->setLogger(new NullLogger());
        }
        return $this->logger;
    }

    /**
     * Set the logger
     *
     * @param LoggerInterface $logger
     * @return void
     */
    public function setLogger(LoggerInterface $logger): void {
        $this->logger = $logger;
    }

}
