<?php

declare(strict_types=1);

namespace Manju\Exceptions;

class ValidationError extends \InvalidArgumentException {

    use LogException;
}
