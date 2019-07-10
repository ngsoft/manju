<?php

declare(strict_types=1);

namespace Manju\Converters;

use Manju\Interfaces\Converter;

class Number implements Converter {

    /** {@inheritdoc} */
    public static function convertFromBean($value) {
        if (is_numeric($value)) return intval($value);
        return null;
    }

    /** {@inheritdoc} */
    public static function convertToBean($value) {
        return self::convertFromBean($value) ?? 0;
    }

    /** {@inheritdoc} */
    public static function getTypes(): array {
        return ["integer", "int"];
    }

    /** {@inheritdoc} */
    public static function isValid($value) {
        return gettype($value) === "integer";
    }

}
