<?php

declare(strict_types=1);

namespace Manju\Converters;

use Manju\Interfaces\Converter;

class Double implements Converter {

    /** {@inheritdoc} */
    public static function convertFromBean($value) {
        if (is_numeric($value)) return doubleval($value);
        return null;
    }

    /** {@inheritdoc} */
    public static function convertToBean($value) {
        if (is_double($value) or is_int($value)) return $value;
        if (is_numeric($value)) return doubleval($value);
        return null;
    }

    /** {@inheritdoc} */
    public static function getTypes(): array {
        return ["double", "float"];
    }

    /** {@inheritdoc} */
    public static function isValid($value) {
        return (gettype($value) === "double") or ( gettype($value) === "integer");
    }

}
