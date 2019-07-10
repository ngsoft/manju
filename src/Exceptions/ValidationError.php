<?php

namespace Manju\Exceptions;

use InvalidArgumentException,
    Manju\ORM,
    Psr\Log\LoggerInterface;

class ValidationError extends InvalidArgumentException {

    public function __construct(string $message = "", int $code = 0, $previous = NULL) {
        $logger = ORM::getLogger();
        if ($logger instanceof LoggerInterface) $logger->error(get_class($this) . ": " . $message);
        parent::__construct($message, $code, $previous);
    }

}
