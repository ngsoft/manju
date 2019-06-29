<?php

namespace Manju\Converters;

use Manju\Interfaces\Converter;

class Number implements Converter {

    /** {@inheritdoc} */
    public function convertFromBean($value) {
        $value = $value !== null ? $value : 0;
        return intval($value);
    }

    /** {@inheritdoc} */
    public function convertToBean($value) {
        $value = $this->convertFromBean($value);
        if (!is_nan($value)) return $value;
        return 0;
    }

    /** {@inheritdoc} */
    public function getTypes(): array {
        return ["integer", "int"];
    }

    /** {@inheritdoc} */
    public function isValid($value) {
        return gettype($value) === "integer";
    }

}
