<?php

declare(strict_types=1);

namespace NGSOFT\Manju;

use RedBeanPHP\OODBBean;

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
        if (in_array($method, array('update', 'open', 'delete', 'after_delete', 'after_update', 'dispense'), TRUE)) {

            $entity = $this->getEntity();
            if ($entity instanceof Entity) {

                $eventClass = sprintf('\\NGSOFT\\Manju\\Events\\%s', ucfirst($method));
                if (class_exists($eventClass)) {
                    $event = new $eventClass($this, $entity);
                }




                return null;
            }
        }

        parent::__call($method, $args);
    }

}
