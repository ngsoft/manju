<?php

declare(strict_types=1);

namespace NGSOFT\ORM\Events;

class Sync extends FuseEvent {

    public function onEvent(): void {

        // todo: sync bean with entity


        parent::onEvent();
    }

}
