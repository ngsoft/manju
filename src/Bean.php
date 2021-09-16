<?php

declare(strict_types=1);

namespace NGSOFT\Manju;

use NGSOFT\ORM\Events\Fuse,
    RedBeanPHP\OODBBean;

class Bean extends OODBBean {

    /**
     * Get Related Entity
     * @return Entity|null
     */
    public function getEntity(): ?Entity {
        $entity = $this->getMeta('model');
        return $entity instanceof Entity ? $entity : null;
    }

    public function __call($method, $args) {

        // intercept fuse Events but only if registered entity
        if (
                in_array($method, array('update', 'open', 'delete', 'after_delete', 'after_update', 'dispense'), TRUE) and
                ($entity = $this->getEntity()) instanceof Entity
        ) {
            $eventClass = Fuse::FUSE_EVENTS[$method];
            $event = new $eventClass($this, $entity);
            // keep old behaviour
            if (method_exists($entity, $method)) {
                $entity->$method();
            }
            //dispatches Event




            return null;
        }

        parent::__call($method, $args);
    }

}
