<?php

namespace Manju\Converters;

use DateTime;
use Manju\{
    Exceptions\ManjuException, Interfaces\Converter
};

class Date implements Converter {

    const FORMAT = "Y-m-d H:i:s";

    /** {@inheritdoc} */
    public function convertFromBean($value) {
        if ($value instanceof DateTime) return $value;
        elseif (is_string($value)) return new DateTime($value);
        elseif (is_int($value)) return new DateTime(date(self::FORMAT, $value));
        else return new DateTime('now');
    }

    /** {@inheritdoc} */
    public function convertToBean($value) {
        if ($value instanceof DateTime) return $value;
        if (is_numeric($value)) return new DateTime(date(self::FORMAT, $value));
        elseif (is_string($value)) return new DateTime($value);
        throw new ManjuException("Cannot Convert to \DateTime");
    }

    /** {@inheritdoc} */
    public function getTypes(): array {
        return ["date", "DateTime", "datetime"];
    }

    /** {@inheritdoc} */
    public function isValid($value) {
        return ($value instanceof DateTime) or is_numeric($value);
    }

}
