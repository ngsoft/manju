<?php

declare(strict_types=1);

namespace NGSOFT\ORM\Events;

class Update extends ORMEvent {

    public function onEvent(): void {
        // todo: convert scalar types to be written in the database



        parent::onEvent();
    }

}
