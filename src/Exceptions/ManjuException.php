<?php

namespace Manju\Exceptions;

use Manju\ORM,
    Psr\Log\LoggerInterface,
    Exception;

class ManjuException extends Exception {

    use LogException;
}
