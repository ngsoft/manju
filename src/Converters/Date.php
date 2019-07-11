<?php

declare(strict_types=1);

namespace Manju\Converters;

use DateTime,
    Manju\Interfaces\Converter;

class Date implements Converter {

    const FORMAT = "Y-m-d H:i:s";

    /** {@inheritdoc} */
    public static function convertFromBean($value) {
        if ($value instanceof DateTime) return $value;
        elseif (is_string($value)) return new DateTime($value);
        elseif (is_int($value)) return new DateTime(date(self::FORMAT, $value));
        return null;
    }

    /** {@inheritdoc} */
    public static function convertToBean($value) {
        if ($value instanceof DateTime) return $value;
        if (is_numeric($value) and ! is_nan((int) $value)) return new DateTime(date(self::FORMAT, (int) $value));
        elseif (is_string($value)) return new DateTime($value);
        return null;
    }

    /** {@inheritdoc} */
    public static function getTypes(): array {
        return ["DateTime", "\\Datetime"];
    }

    /** {@inheritdoc} */
    public static function isValid($value) {
        return ($value instanceof DateTime) or is_numeric($value);
    }

}
