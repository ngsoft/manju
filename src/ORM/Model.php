<?php

namespace Manju\ORM;

use ArrayIterator,
    DateTime,
    ErrorException,
    JsonSerializable,
    Manju\Helpers\BeanHelper,
    NGSOFT\Tools\Interfaces\ArrayAccess;
use RedBeanPHP\{
    OODBBean, SimpleModel
};
use function NGSOFT\Tools\toCamelCase;

class Model extends SimpleModel implements ArrayAccess, JsonSerializable {

    ////////////////////////////   CONSTANTS   ////////////////////////////

    const VALID_BEAN = '/^[a-z][a-z0-9]+$/';
    const VALID_PARAM = '/^[a-zA-Z]\w+$/';

    ////////////////////////////   DEFAULTS PROPERTIES   ////////////////////////////

    /**
     * @var int
     */
    protected $id;

    /**
     * @var DateTime
     */
    protected $created_at = null;

    /**
     * @var DateTime
     */
    protected $updated_at = null;

    /**
     * Get the Entry ID
     * @return int
     */
    public function getId(): int {
        return $this->id;
    }

    /**
     * Get Entry Creation Date
     * @return DateTime|null
     */
    public function getCreatedAt(): ?DateTime {
        return $this->created_at;
    }

    /**
     * Get Last Update Date
     * @return DateTime|null
     */
    public function getUpdatedAt(): ?DateTime {
        return $this->updated_at;
    }

    ////////////////////////////   MetaDatas   ////////////////////////////

    /**
     * Get Model Metadatas
     * @param string|null $key
     * @return mixed
     */
    public function getMeta(string $key = null) {
        $meta = BeanHelper::$metadatas[get_class($this)];
        if ($key === null) return $meta;
        return $meta[$key] ?? null;
    }

    ////////////////////////////   ArrayAccess   ////////////////////////////

    /**
     * getProp()
     * @param string $prop
     * @return string
     */
    private function getGetterMethod(string $prop): string {
        return sprintf("get%s", toCamelCase($prop));
    }

    /**
     * setProp($value)
     * @param string $prop
     * @return string
     */
    private function getSetterMethod(string $prop): string {
        return sprintf("get%s", toCamelCase($prop));
    }

    /**
     * Exports Model data to Array
     * @return array
     */
    public function toArray(): array {
        $array = [];
        foreach ($this->getMeta("properties") as $key) {
            $getter = $this->getGetterMethod($key);
            if (method_exists($this, $getter)) $array[$key] = $this->{$getter}();
        }
        return $array;
    }

    /** {@inheritdoc} */
    public function offsetExists($offset) {
        $getter = $this->getGetterMethod($offset);
        return method_exists($this, $getter);
    }

    /** {@inheritdoc} */
    public function offsetGet($offset) {
        $getter = $this->getGetterMethod($offset);
        if (method_exists($this, $getter)) {
            return $this->{$getter}();
        }
        throw new ErrorException("Invalid Property $offset");
    }

    /** {@inheritdoc} */
    public function offsetSet($offset, $value) {
        $setter = $this->getSetterMethod($offset);
        if (method_exists($this, $setter)) {
            $this->{$setter}($value);
        } else throw new ErrorException("Invalid Property $offset");
    }

    /** {@inheritdoc} */
    public function offsetUnset($offset) {
        $setter = $this->getSetterMethod($offset);
        if (method_exists($this, $setter)) {
            $this->{$setter}(null);
        }
    }

    /** {@inheritdoc} */
    public function count() {
        return count($this->toArray());
    }

    /** {@inheritdoc} */
    public function getIterator() {
        return new ArrayIterator($this->toArray());
    }

    public function jsonSerialize() {
        return $this->toArray();
    }

    ////////////////////////////   __magic_methods   ////////////////////////////

    /** {@inheritdoc} */
    public function __get($prop) {
        return $this->offsetGet($prop);
    }

    /** {@inheritdoc} */
    public function __set($prop, $value) {
        $this->offsetSet($prop, $value);
    }

    /** {@inheritdoc} */
    public function __isset($key) {
        return $this->offsetExists($key);
    }

    /** {@inheritdoc} */
    public function __clone() {
        if ($this->bean instanceof OODBBean) $this->bean = clone $this->bean;
    }

    /** {@inheritdoc} */
    public function __toString() {
        return var_export($this->toArray(), true);
    }

    /** {@inheritdoc} */
    public function __unset($prop) {
        $this->offsetUnset($prop);
    }

    ////////////////////////////   Events   ////////////////////////////

    /**
     * Reset the model with its defaults values
     * @internal
     */
    public function _clear() {
        if ($meta = $this->getMeta()) {
            foreach ($meta["properties"] as $prop) {
                $this->{$prop} = $meta["defaults"]["prop"] ?? null;
            }
            $this->id = 0;
            //relations
        }
    }

    /**
     * Sync Model with Bean
     * @internal
     */
    public function _reload() {
        $this->_clear();
        if ($meta = $this->getMeta()) {
            $b = $this->bean;
            foreach ($meta["converters"] as $converter => $prop) {
                $value = $b->{$prop};
                if ($value !== null) $this->{$prop} = $converter->convertFromBean($value);
            }
            if (count($meta["unique"])) $b->setMeta("sys.uniques", $meta["unique"]);
            //relations
        }
    }

}
