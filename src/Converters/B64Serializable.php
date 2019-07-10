<?php

namespace Manju\Converters;

use InvalidArgumentException;
use Manju\Interfaces\Converter;
use Serializable;

class B64Serializable implements Converter {

    /** {@inheritdoc} */
    public function convertFromBean($value) {
        if (is_string($value) and mb_strlen($value) > 0) return $this->b64unserialize($value);
        return null;
    }

    /** {@inheritdoc} */
    public function convertToBean($value) {
        if (is_array($value) or $value instanceof Serializable) return $this->b64serialize($value);
        return "";
    }

    /** {@inheritdoc} */
    public function getTypes(): array {
        return ["object", "array"];
    }

    /** {@inheritdoc} */
    public function isValid($value) {
        return is_array($value) or $value instanceof Serializable;
    }

    /**
     * Serialize and encode to base 64
     * @param array|Serializable $value
     * @return string
     */
    public function b64serialize($value): string {
        if (!is_array($value) && !($value instanceof Serializable)) {
            throw new InvalidArgumentException("Cannot serialize value not Serializable");
        }
        $str = serialize($value);
        $result = base64_encode($str);
        return $result;
    }

    /**
     * Unserialize Base 64 encoded String
     * @param string $value
     * @return array|Serializable|null
     */
    public function b64unserialize(string $value) {
        $str = base64_decode($value);
        $obj = unserialize($str);
        return $obj;
    }

}
