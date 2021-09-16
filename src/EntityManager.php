<?php

declare(strict_types=1);

namespace NGSOFT\Manju;

use NGSOFT\Events\EventListener;
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
     * Set Event Listener
     * @param ListenerProviderInterface $eventListener
     * @return static
     */
    public function setEventListener(ListenerProviderInterface $eventListener): self {
        $this->eventListener = $eventListener;
        return $this;
    }

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
        if (is_null($this->eventListener)) $this->setEventListener(new EventListener());
        return $this->eventListener;
    }

    /** @return EventDispatcherInterface */
    public function getEventDispatcher(): EventDispatcherInterface {
        if (is_null($this->eventDispatcher)) $this->setEventDispatcher(new \NGSOFT\Events\EventDispatcher($this->getEventListener()));
        return $this->eventDispatcher;
    }

    public function load(string $type, int $id): Entity {


        return new Entity();
    }

}
