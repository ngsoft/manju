<?php

declare(strict_types=1);

namespace Manju\Exceptions;

use Manju\ORM,
    Psr\Log\LoggerInterface,
    Throwable;

trait LogException {

    /**
     * @suppress PhanTraitParentReference
     * @param string $message
     * @param int $code
     * @param Throwable $previous
     */
    public function __construct(string $message = "", int $code = 0, $previous = NULL) {
        if ($this instanceof Throwable) {
            $logger = ORM::getLogger(); $loglevel = ORM::LOGLEVEL;
            if ($logger instanceof LoggerInterface) {
                $infos = [
                    "Exception" => get_class($this),
                    "code" => $code,
                    "file" => $this->getFile(),
                    "line" => $this->getLine()
                ];
                $logger->log($loglevel, $message, $infos);
                parent::__construct($message, $code, $previous);
            }
        }
    }

}
