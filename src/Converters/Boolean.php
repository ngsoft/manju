<?php

namespace Manju\Converters;

class Boolean implements \Manju\Interfaces\Converter {

    public function convertFromBean($value) {

    }

    public function convertToBean($value) {

    }

    public function getTypes(): array {
        return ["bool", "boolean"];
    }

    public function isValid($value) {
        return gettype($value) === "boolean";
    }

}
