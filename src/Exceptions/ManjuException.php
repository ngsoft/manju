<?php

use Manju\ORM,
    Psr\Log\LoggerInterface;

namespace Manju\Exceptions;

class ManjuException extends Exception {

    public function __construct(string $message = "", int $code = 0, $previous = NULL) {
        $logger = ORM::getLogger();
        if ($logger instanceof LoggerInterface) $logger->error(get_class($this) . ": " . $message);
        parent::__construct($message, $code, $previous);
    }

}
