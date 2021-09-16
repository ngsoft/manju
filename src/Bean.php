<?php

declare(strict_types=1);

namespace NGSOFT\Manju;

use NGSOFT\ORM\Events\{
    AfterUpdate, Fuse, Open, Sync, Update, Validate
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

    /** {@inheritdoc} */
    public function __call($method, $args) {

        // intercept fuse Events but only if registered entity
        if (
                in_array($method, array('update', 'open', 'delete', 'after_delete', 'after_update', 'dispense'), TRUE) and
                ($entity = $this->getEntity()) instanceof Entity
        ) {
            $eventClass = Fuse::FUSE_EVENTS[$method];
            $events = [];

            if (in_array($eventClass, [Update::class])) {
                // validate Entity values
                $events[] = new Validate($this, $this->getEntity());
            }


            if (in_array($eventClass, [Open::class, AfterUpdate::class])) {
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
            // no need to execute RedBean code as a listener will call the SimpleModel method if it exists.
            return null;
        }

        parent::__call($method, $args);
    }

}
