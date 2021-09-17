<?php

declare(strict_types=1);

namespace NGSOFT\Manju;

use NGSOFT\{
    Events\EventDispatcher, ORM\Events\Fuse, ORM\Events\FuseEvent
};
use Psr\EventDispatcher\{
    EventDispatcherInterface, ListenerProviderInterface
};
use Symfony\Component\EventDispatcher\EventDispatcherInterface as SymfonyEventDispatcherInterface;

final class EntityManager {

    const VERSION = '3.0';

    /** @var ListenerProviderInterface */
    private $eventListener;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /**
     * Set Event Dispatcher
     * @param EventDispatcherInterface $eventDispatcher
     * @return void
     */
    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher): void {
        $this->eventDispatcher = $eventDispatcher;
        if (is_null($this->eventListener)) {
            //symphony event dispatcher compatibiliy
            $this->eventListener = $eventDispatcher instanceof SymfonyEventDispatcherInterface ? $eventDispatcher : new Fuse();
            // register Listeners
            foreach (FuseEvent::FUSE_EVENTS as $eventType) {
                $this->eventListener->addListener($eventType, function (FuseEvent $event) {
                    $event->onEvent();
                }, 10);
            }
        }
    }

    /**  @return ListenerProviderInterface */
    public function getEventListener(): ListenerProviderInterface {
        return $this->eventListener;
    }

    /** @return EventDispatcherInterface */
    public function getEventDispatcher(): EventDispatcherInterface {
        if (is_null($this->eventDispatcher)) {
            $this->setEventDispatcher(new EventDispatcher());
        }
        return $this->eventDispatcher;
    }

    public function load(string $type, int $id): Entity {


        return new Entity();
    }

}
