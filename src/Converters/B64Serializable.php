<?php

declare(strict_types=1);

namespace Manju\Converters;

use InvalidArgumentException,
    Manju\Interfaces\Converter,
    Serializable;
use function mb_strlen;

class B64Serializable implements Converter {

    /** {@inheritdoc} */
    public static function convertFromBean($value) {
        if (is_string($value) and mb_strlen($value) > 0) return self::b64unserialize($value);
        return null;
    }

    /** {@inheritdoc} */
    public static function convertToBean($value) {
        if (is_array($value) or $value instanceof Serializable) return self::b64serialize($value);
        return "";
    }

    /** {@inheritdoc} */
    public static function getTypes(): array {
        return ["object", "array"];
    }

    /** {@inheritdoc} */
    public static function isValid($value) {
        return is_array($value) or $value instanceof Serializable;
    }

    /**
     * Serialize and encode to base 64
     * @param array|Serializable $value
     * @return string
     */
    public static function b64serialize($value): string {
        if (!is_array($value) && !($value instanceof Serializable)) {
            throw new InvalidArgumentException("Cannot serialize value not Serializable");
        }
        return base64_encode(serialize($value));
    }

    /**
     * Unserialize Base 64 encoded String
     * @param string $value
     * @return array|Serializable|null
     */
    public static function b64unserialize(string $value) {

        return (
                (mb_strlen($value) > 0)
                and ( $serialized = base64_decode($value, true))
                and ( $obj = @unserialize($serialized))
                ) ? $obj : null;
    }

}
