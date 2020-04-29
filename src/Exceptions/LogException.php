<?php

declare(strict_types=1);

namespace Manju\Exceptions;

use Manju\ORM,
    Psr\Log\LoggerInterface;

trait LogException {

    public function __construct(string $message = "", int $code = 0, $previous = NULL) {
        if ($this instanceof \Throwable) {
            $logger = ORM::getLogger(); $loglevel = ORM::getLogLevel();
            if ($logger instanceof LoggerInterface) {
                $infos = [
                    "Exception" => get_class($this),
                    "code" => $code,
                    "file" => $this->getFile(),
                    "line" => $this->getLine()
                ];
                $logger->log($loglevel, $message, $infos);
            }
            parent::__construct($message, $code, $previous);
        }
    }

}
