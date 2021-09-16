<?php

declare(strict_types=1);

namespace NGSOFT\Manju;

use NGSOFT\ORM\Events\{
    AfterUpdate, Fuse, Load, Open, Update
};
use RedBeanPHP\OODBBean;

class Bean extends OODBBean {

    /** @var EntityManager */
    protected $entityManager;

    /**
     * Get Related Entity
     * @return Entity|null
     */
    public function getEntity(): ?Entity {
        $entity = $this->getMeta('model');
        return $entity instanceof Entity ? $entity : null;
    }

    public function getEntityManager(): EntityManager {
        return $this->entityManager;
    }

    public function setEntityManager(EntityManager $entityManager): void {
        $this->entityManager = $entityManager;
    }

    public function __call($method, $args) {

        // intercept fuse Events but only if registered entity
        if (
                in_array($method, array('update', 'open', 'delete', 'after_delete', 'after_update', 'dispense'), TRUE) and
                ($entity = $this->getEntity()) instanceof Entity
        ) {
            $eventClass = Fuse::FUSE_EVENTS[$method];
            $events = [];

            if (in_array($eventClass, [Open::class, Update::class, AfterUpdate::class])) {
                //sync Entity with Bean
                $events[] = new Sync($this, $this->getEntity());
            }

            $events[] = new $eventClass($this, $entity);
            //dispatches Events
            foreach ($events as $event) {
                $this
                        ->getEntityManager()
                        ->getEventDispatcher()
                        ->dispatch($event);
            }
            return null;
        }

        parent::__call($method, $args);
    }

}
