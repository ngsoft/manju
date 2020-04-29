<?php

namespace Manju\Exceptions;

use InvalidArgumentException,
    Manju\ORM,
    Psr\Log\LoggerInterface;

class ValidationError extends InvalidArgumentException {

    use LogException;
}
