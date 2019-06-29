<?php

namespace Manju\Interfaces;

interface Converter {

    /**
     * Returns types managed by the converter
     * @return array<string>
     */
    public function getTypes(): array;

    /**
     * Convert value from bean to a correct scallar value
     * @param mixed $value
     * @return mixed
     */
    public function convertFromBean($value);

    /**
     * Convert model value to a value managed by redbean
     * @param mixed $value
     */
    public function convertToBean($value);

    /**
     * Check if value is compatible with the converter
     * @param mixed $value
     */
    public function isValid($value);
}
