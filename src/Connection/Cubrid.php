<?php

declare(strict_types=1);

namespace NGSOFT\Manju\Connection;

class Cubrid extends PostgreSQL {

    protected function getDBType(): string {
        return 'CUBRID';
    }

    protected function getPrefix(): string {
        return 'cubrid';
    }

}
