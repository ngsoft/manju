<?php

declare(strict_types=1);

namespace NGSOFT\Manju\Connection;

class Cubrid extends PostgreSQL {

    /** {@inheritdoc} */
    protected function getDBType(): string {
        return 'CUBRID';
    }

    /** {@inheritdoc} */
    protected function getPrefix(): string {
        return 'cubrid';
    }

}
