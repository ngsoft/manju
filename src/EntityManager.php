<?php

declare(strict_types=1);

namespace NGSOFT\Manju;

final class EntityManager {

    const VERSION = '3.0';

    public function load(string $type, int $id): Entity {


        return new Entity();
    }

}
