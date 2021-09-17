<?php

declare(strict_types=1);

namespace NGSOFT\Manju;

use NGSOFT\{
    Events\EventDispatcher, Manju\Events\FuseEvent
};
use Psr\{
    Cache\CacheItemPoolInterface, EventDispatcher\EventDispatcherInterface, EventDispatcher\ListenerProviderInterface
};
use Symfony\Component\EventDispatcher\EventDispatcherInterface as SymfonyEventDispatcherInterface;

final class EntityManager {

    const VERSION = '3.0';

    /** @var ListenerProviderInterface */
    private $eventListener;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /** @var CacheItemPoolInterface */
    private $cachePool;

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
     * @return CacheItemPoolInterface|null
     */
    public function getCachePool(): ?CacheItemPoolInterface {
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

}
