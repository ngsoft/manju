<?php

namespace Manju\Converters;

class Boolean implements \Manju\Interfaces\Converter {

    public function convertFromBean($value) {
        return (int) $value === 1;
    }

    public function convertToBean($value) {
        return $value === true ? 1 : 0;
    }

    public function getTypes(): array {
        return ["bool", "boolean"];
    }

    public function isValid($value) {
        return gettype($value) === "boolean";
    }

}
