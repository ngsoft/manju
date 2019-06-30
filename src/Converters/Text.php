<?php

namespace Manju\Converters;

use Manju\Interfaces\Converter;

class Text implements Converter {

    /** {@inheritdoc} */
    public function convertFromBean($value) {
        return (string) $value;
    }

    /** {@inheritdoc} */
    public function convertToBean($value) {
        return (string) $value;
    }

    /** {@inheritdoc} */
    public function getTypes(): array {
        return ["string", "text"];
    }

    /** {@inheritdoc} */
    public function isValid($value) {
        return gettype($value) === "string";
    }

}
