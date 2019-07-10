<?php

namespace Manju\ORM;

use ArrayIterator,
    DateTime,
    JsonSerializable;
use Manju\{
    Exceptions\InvalidProperty, Exceptions\ValidationError, Helpers\BeanHelper, ORM
};
use NGSOFT\Tools\Interfaces\ArrayAccess;
use RedBeanPHP\{
    OODBBean, SimpleModel
};
use function NGSOFT\Tools\toCamelCase;

/**
 * @property-read int $id
 * @property-read DateTime $created_at
 * @property-read DateTime $updated_at
 */
class Model extends SimpleModel implements ArrayAccess, JsonSerializable {

    ////////////////////////////   CONSTANTS   ////////////////////////////

    const VALID_BEAN = '/^[a-z][a-z0-9]+$/';
    const VALID_PARAM = '/^[a-zA-Z]\w+$/';
    const TO_MANY_LIST = '/^(shared|own|xown)([A-Z][a-z0-9]+)List$/';

    ////////////////////////////   DEFAULTS PROPERTIES   ////////////////////////////

    /** @var string|null */
    public static $type;

    /**
     * @var int
     */
    private $id;

    /**
     * @var DateTime|null
     */
    private $created_at;

    /**
     * @var DateTime|null
     */
    private $updated_at;

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

    ////////////////////////////   SQL Helpers   ////////////////////////////

    /**
     * Finds entries using an optional SQL statement
     * @param string|null $sql SQL query to find the desired bean, starting right after WHERE clause
     * @param array $bindings array of values to be bound to parameters in query
     * @return array<static>
     */
    public static function find(string $sql = null, array $bindings = []): array {
        if (($type = BeanHelper::$metadatas[static::class]->type ?? null)) {
            return array_map(function ($bean) {
                return $bean->box();
            }, ORM::find($type, $sql, $bindings));
        }
        return [];
    }

    /**
     * Returns the first entry
     * @param string $sql SQL query to find the desired entry, starting right after WHERE clause
     * @param array $bindings array of values to be bound to parameters in query
     * @return static|null
     */
    public static function findOne(string $sql = null, array $bindings = []) {
        if (($type = BeanHelper::$metadatas[static::class]->type ?? null)) {
            if ($result = ORM::findOne($type, $sql, $bindings)) return $result->box();
        }
        return null;
    }

    /**
     * Count the number of entries of current Model
     * @param string $sql additional SQL snippet
     * @param array $bindings parameters to bind to SQL
     * @return int
     */
    public static function countEntries(string $sql = "", array $bindings = []): int {
        if (($type = BeanHelper::$metadatas[static::class]->type ?? null)) {
            return ORM::count($type, $sql, $bindings);
        }
        return 0;
    }

////////////////////////////   CRUD   ////////////////////////////

    /**
     * Loads a bean with the data corresponding to the id
     *
     * @param int|null $id if not set it will just create an empty Model
     * @return static Instance of Model
     */
    public static function load(int $id = null) {
        $i = new static();
        BeanHelper::dispenseFor($i, $id);
        return $i;
    }

    /**
     * Creates A new Model instance
     * @return static
     */
    public static function create() {
        return self::load();
    }

    /**
     * Remove Entry from the database
     * @return void
     */
    public function trash(): void {
        if ($this->bean instanceof OODBBean) {
            ORM::trash($this->bean);
        }
    }

    /**
     * Reloads Data for current Entry from the database
     * @return static New instance with fresh data
     */
    public function fresh() {
        if (
                ($this->bean instanceof OODBBean)
                and $this->bean->id > 0
        ) {
            return ORM::load($this->getMeta("type"), $this->bean->id)->box();
        }
        return $this;
    }

    /**
     * Store current Entry into the database
     * @return int|null
     */
    public function save(): ?int {
        if (!($this->bean)) BeanHelper::dispenseFor($this);
        if ($this->bean instanceof OODBBean) {
            $this->bean->setMeta("tainted", true);
            try {
                $id = (int) ORM::store($this->bean);
            } catch (\Throwable $exc) {
                echo $exc->getCode();
            }
        }
        return $id ?? null;
    }

    ////////////////////////////   MetaDatas   ////////////////////////////

    /**
     * Get Model Metadatas
     *
     * @param string|null $key
     * @return mixed
     */
    public function getMeta(string $key = null) {
        $meta = BeanHelper::$metadatas[get_class($this)];
        if ($key === null) return $meta;
        return $meta->{$key} ?? null;
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
        return sprintf("set%s", toCamelCase($prop));
    }

    /**
     * Exports Model data to Array
     * @return array
     */
    public function toArray(): array {
        $array = [];
        $props = array_merge(["id"], $$this->getMeta("properties"));
        foreach ($props as $key) {
            $getter = $this->getGetterMethod($key);
            if (method_exists($this, $getter)) $array[$key] = $this->{$getter}();
        }
        return $array;
    }

    /**
     * Import array into new Model
     * @param array $array
     * @return static
     */
    public static function __set_state(array $array) {
        $i = self::load();
        foreach ($array as $k => $v) {
            $i->offsetSet($k, $v);
        }
        return $i;
    }

    /** {@inheritdoc} */
    public function offsetExists($offset) {
        $getter = $this->getGetterMethod($offset);
        return method_exists($this, $getter) && $this->{$getter}() !== null;
    }

    /** {@inheritdoc} */
    public function &offsetGet($offset) {
        $getter = $this->getGetterMethod($offset);
        if (method_exists($this, $getter)) {
            $value = &$this->{$getter}();
            return $value;
        } else throw new InvalidProperty("Invalid Property $offset");
    }

    /** {@inheritdoc} */
    public function offsetSet($offset, $value) {

        $setter = $this->getSetterMethod($offset);
        if (method_exists($this, $setter)) {
            $this->{$setter}($value);
        } else throw new InvalidProperty("Invalid Property $offset");
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

    /** {@inheritdoc} */
    public function jsonSerialize() {
        return $this->toArray();
    }

    ////////////////////////////   __magic_methods   ////////////////////////////

    /** {@inheritdoc} */
    public function &__get($prop) {
        $value = &$this->offsetGet($prop);
        return $value;
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
            foreach ($meta->properties as $prop) {
                $this->{$prop} = $meta->defaults->{$prop} ?? null;
            }
            $this->id = 0;
        }
    }

    /**
     * Sync Model with Bean
     * @internal
     */
    public function _reload() {
        //$this->_clear();
        if ($meta = $this->getMeta()) {
            $b = $this->bean;
            foreach ($meta->converters as $prop => $converter) {
                $value = $b->{$prop};
                if ($value !== null) $this->{$prop} = $converter::convertFromBean($value);
            }
            if (count($meta->unique)) $b->setMeta("sys.uniques", $meta->unique);
        }
    }

    /**
     * Validate Model Datas
     * @internal
     * @suppress PhanUndeclaredMethod
     * @throws ValidationError Prevents Redbeans from Writing wrong datas
     */
    public function _validate() {
        if ($meta = $this->getMeta()) {
            foreach ($meta->required as $prop) {
                if ($this->{$prop} === null) throw new ValidationError(get_class($this) . '::$' . $prop . " Cannot be NULL");
            }
            foreach ($meta->converters as $prop => $converter) {

                if (!$converter::isValid($this->{$prop})) {
                    throw new ValidationError(
                            get_class($this) . '::$' . $prop . " Invalid Type " .
                            $converter::getTypes()[0] . " requested but " .
                            gettype($this->{$prop}) . " given."
                    );
                }

                if (
                        method_exists($this, "validateModel")
                        and ( false === $this->validateModel($prop, $this->{$prop}))
                ) {
                    throw new ValidationError(get_class($this) . "::validateModel($prop, ...) failed the validation test.");
                }
            }
        }
    }

    /**
     * Write datas to bean
     * @internal
     */
    public function _update() {
        if ($meta = $this->getMeta()) {
            if ($meta->timestamps === true) {
                $now = new DateTime();
                if ($this->created_at === null) $this->created_at = $now;
                $this->updated_at = $now;
            }
            foreach ($meta->converters as $prop => $converter) {
                if ($prop === "id") continue;
                $value = $converter::convertToBean($this->{$prop});
                $this->bean->{$prop} = $value;
            }
        }
    }

}
