<?php

declare(strict_types=1);

namespace NGSOFT\Manju;

use NGSOFT\{
    Events\EventDispatcher, ORM\Events\Fuse
};
use Psr\EventDispatcher\{
    EventDispatcherInterface, ListenerProviderInterface
};

final class EntityManager {

    const VERSION = '3.0';

    /** @var ListenerProviderInterface */
    private $eventListener;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /**
     * Set Event Dispatcher
     * @param EventDispatcherInterface $eventDispatcher
     * @return static
     */
    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher): self {
        $this->eventDispatcher = $eventDispatcher;
        return $this;
    }

    /**  @return ListenerProviderInterface */
    public function getEventListener(): ListenerProviderInterface {
        return $this->eventListener;
    }

    /** @return EventDispatcherInterface */
    public function getEventDispatcher(): EventDispatcherInterface {
        if (is_null($this->eventDispatcher)) {
            $dispatcher = new EventDispatcher();
            $dispatcher->setEventListener(new Fuse($dispatcher));
            $this->setEventDispatcher($dispatcher);
        }
        return $this->eventDispatcher;
    }

    public function load(string $type, int $id): Entity {


        return new Entity();
    }

}
