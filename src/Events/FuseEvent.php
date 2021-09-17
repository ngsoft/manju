<?php

declare(strict_types=1);

namespace NGSOFT\ORM\Events;

use NGSOFT\{
    Events\StoppableEvent, Manju\Entity
};
use RedBeanPHP\OODBBean;

abstract class FuseEvent extends StoppableEvent {

    const FUSE_EVENTS = [
        'update' => Update::class,
        'open' => Open::class,
        'delete' => Delete::class,
        'after_delete' => AfterDelete::class,
        'after_update' => AfterUpdate::class,
        'dispense' => Dispense::class,
        'validate' => Validate::class,
        'sync' => Sync::class,
    ];

    /** @var Entity|null */
    private $entity;

    /** @var OODBBean */
    private $bean;

    /**
     * Called when event is dispatched
     */
    public function onEvent(): void {
        $class = get_class($this);
        $method = array_search($class, FuseEvent::FUSE_EVENTS);

        if (
                is_string($method) and
                $entity = $this->getEntity() and
                method_exists($entity, $method)
        ) {
            $entity->$method();
        }
    }

    /**
     * Get Entity the event is loaded for
     * @return Entity|null
     */
    public function getEntity(): ?Entity {
        return $this->entity;
    }

    /**
     * Get the Bean the Event is loaded for
     * @return OODBBean
     */
    public function getBean(): OODBBean {
        return $this->bean;
    }

    public function __construct(
            OODBBean $bean,
            Entity $entity = null
    ) {
        $this->bean = $bean;
        $this->entity = $entity;
    }

}
