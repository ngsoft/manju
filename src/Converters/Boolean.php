<?php

namespace Manju\Converters;

use Manju\Interfaces\Converter;

class Boolean implements Converter {

    public static function convertFromBean($value) {
        if (is_string($value)) return $value === "true";
        elseif (is_numeric($value)) return 1 === (int) $value;
        else return $value === true;
    }

    public static function convertToBean($value) {
        return $value === true ? "true" : "false";
    }

    public static function getTypes(): array {
        return ["bool", "boolean"];
    }

    public static function isValid($value) {
        return gettype($value) === "boolean";
    }

}
