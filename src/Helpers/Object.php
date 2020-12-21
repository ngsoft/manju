<?php

namespace Manju\Helpers;

use ArrayAccess,
    Countable,
    Iterator,
    JsonSerializable,
    Serializable;

class Object implements ArrayAccess, Countable, Iterator, JsonSerializable, Serializable {

    /** @var array */
    protected $storage = [];

    /**
     * Creates a new Object
     * @return static
     */
    public static function create() {
        return new static();
    }

    /**
     * Creates an Objec from an array
     * @param array $array
     * @return static
     */
    public static function from(array &$array) {

        $obj = static::create();
        $obj->storage = &$array;
        return $obj;
    }

    /**
     * Creates an Object
     * @param array $array
     */
    public function __construct(array &$array = []) {
        $this->storage = &$array;
    }

    /** {@inheritdoc} */
    public function offsetExists($offset) {
        return array_key_exists($offset, $this->storage);
    }

    /** {@inheritdoc} */
    public function &offsetGet($offset) {
        if ($offset == null) {
            $this->storage[] = [];
            $offset = array_key_last($this->storage);
        }

        if (isset($this->storage[$offset])) {
            if (is_array($this->storage[$offset])) {
                $array = &$this->storage[$offset];
                $result = clone $this;
                $result->storage = &$array;
                return $result;
            } else $result = &$this->storage[$offset];
        } else $result = null;
        return $result;
    }

    /** {@inheritdoc} */
    public function offsetSet($offset, $value) {
        if ($offset === null) $this->storage[] = $value;
        else $this->storage[$offset] = $value;
    }

    /** {@inheritdoc} */
    public function offsetUnset($offset) {
        unset($this->storage[$offset]);
    }

    /** {@inheritdoc} */
    public function count() {
        return count($this->storage);
    }

    /** {@inheritdoc} */
    public function current() {
        $key = $this->key();
        if ($key === null) return false;
        return $this->offsetGet($key);
    }

    /** {@inheritdoc} */
    public function key() {
        return key($this->storage);
    }

    /** {@inheritdoc} */
    public function next() {
        next($this->storage);
    }

    /** {@inheritdoc} */
    public function rewind() {
        reset($this->storage);
    }

    /** {@inheritdoc} */
    public function valid() {
        return $this->key() !== null;
    }

    /** {@inheritdoc} */
    public function &__get($name) {
        $value = $this->offsetGet($name);
        return $value;
    }

    /** {@inheritdoc} */
    public function __isset($name) {
        return $this->offsetExists($name);
    }

    /** {@inheritdoc} */
    public function __set($name, $value) {
        $this->offsetSet($name, $value);
    }

    /** {@inheritdoc} */
    public function __unset($name) {
        $this->offsetUnset($name);
    }

    /** {@inheritdoc} */
    public function jsonSerialize() {
        return $this->storage;
    }

    public function serialize() {
        return serialize($this->storage);
    }

    public function unserialize($serialized) {
        $array = unserialize($serialized);
        if (is_array($array)) $this->storage = &$array;
    }

}
