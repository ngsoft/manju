<?php

namespace Manju\Converters;

use Manju\Interfaces\Converter;

class Text implements Converter {

    /** {@inheritdoc} */
    public static function convertFromBean($value) {
        return (string) $value;
    }

    /** {@inheritdoc} */
    public static function convertToBean($value) {
        return (string) $value;
    }

    /** {@inheritdoc} */
    public static function getTypes(): array {
        return ["string", "text"];
    }

    /** {@inheritdoc} */
    public static function isValid($value) {
        return gettype($value) === "string";
    }

}
