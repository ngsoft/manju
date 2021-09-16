<?php

declare(strict_types=1);

namespace NGSOFT\ORM\Events;

class Sync extends ORMEvent {

    public function onEvent(): void {

        // todo: sync bean with entity


        parent::onEvent();
    }

}
