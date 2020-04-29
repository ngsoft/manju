<?php

declare(strict_types=1);

namespace Manju\Exceptions;

use Manju\ORM,
    Exception;

class ManjuException extends Exception {

    use LogException;
}
