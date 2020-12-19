<?php

namespace Manju\ORM;

use ArrayAccess,
    ArrayIterator,
    Countable,
    DateTime,
    IteratorAggregate,
    JsonSerializable;
use Manju\{
    Converters\Date, Exceptions\InvalidProperty, Exceptions\ValidationError, Helpers\BeanHelper, Helpers\Collection, ORM
};
use RedBeanPHP\{
    Facade, OODBBean, SimpleModel
};
use Throwable;
use function Manju\toCamelCase;

/**
 * Manju Base Model
 * @property-read int $id
 * @property-read DateTime $created_at
 * @property-read DateTime $updated_at
 */
abstract class Model extends SimpleModel implements Countable, IteratorAggregate, ArrayAccess, JsonSerializable {

    ////////////////////////////   CONSTANTS   ////////////////////////////

    const VERSION = ORM::VERSION;
    const VALID_BEAN = '/^[a-z][a-z0-9]+$/';
    const VALID_PARAM = '/^[a-zA-Z]\w+$/';
    const TO_MANY_LIST = '/^(shared|own|xown)([A-Z][a-z0-9]+)List$/';

    ////////////////////////////   DEFAULTS PROPERTIES   ////////////////////////////

    /** @var string|null */
    public static $type;

    /** @var int */
    private $id = 0;

    /** @var DateTime|null */
    private $created_at;

    /** @var DateTime|null */
    private $updated_at;

    /**
     * Get the Entry ID
     * @return int
     */
    public function getId(): int {
        return intval($this->id);
    }

    /**
     * Get Entry Creation Date
     * @return DateTime
     */
    public function getCreatedAt(): DateTime {
        return $this->created_at ?? new DateTime();
    }

    /**
     * Get Last Update Date
     * @return DateTime
     */
    public function getUpdatedAt(): DateTime {
        return $this->updated_at ?? new DateTime();
    }

    ////////////////////////////   Utils   ////////////////////////////

    /**
     * Get Model Metadatas
     * @param string|null $key
     * @return mixed
     */
    public function getMeta(string $key = null) {
        $meta = BeanHelper::$metadatas[get_class($this)];
        if ($key === null) return $meta;
        return $meta->{$key} ?? null;
    }

    /**
     * Get Metadata Bean Type
     * @return string|null
     */
    public static function getType(): ?string {
        if ($meta = BeanHelper::$metadatas[static::class] ?? null) {
            return $meta->type;
        }
        return null;
    }

    ////////////////////////////   CRUD   ////////////////////////////

    /**
     * Loads a bean with the data corresponding to the id
     *
     * @suppress PhanTypeInstantiateAbstractStatic
     * @param int|null $id if not set it will just create an empty Model
     * @return static Instance of Model
     */
    public static function load(int $id = null) {
        $i = new static();
        BeanHelper::dispenseFor($i, $id);
        return $i;
    }

    /**
     * Creates a new Model instance
     * @param Model|null $model
     * @return static
     */
    public static function create(Model $model = null) {
        if ($model instanceof static) {
            BeanHelper::dispenseFor($model);
            return $model;
        }
        return static::load();
    }

    /**
     * Creates a new Model instance using given array as data
     * @param array $array
     * @return static
     */
    public static function from(array $array) {

        $model = static::create();
        foreach ($array as $key => $val) {
            try {
                $model->offsetSet($key, $val);
            } catch (Throwable $ex) { $ex->getCode(); }
        }

        return $model;
    }

    /**
     * Remove Entry from the database
     * @return void
     */
    public function trash(): void {
        if ($this->bean instanceof OODBBean) {
            Facade::trash($this->bean);
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
            return Facade::load($this->getMeta("type"), $this->bean->id)->box();
        }
        return $this;
    }

    /**
     * Store current Entry into the database
     * @param bool $throws_validation_error
     * @return int|null
     */
    public function save(bool $throws_validation_error = false): ?int {
        if (!($this->bean)) BeanHelper::dispenseFor($this);
        $id = null;
        if ($this->bean instanceof OODBBean) {
            $this->bean->setMeta("tainted", true);
            try {
                $id = (int) Facade::store($this->bean);
            } catch (Throwable $exc) {
                if ($throws_validation_error === true) throw $exc;
            }
        }
        return $id;
    }

    ////////////////////////////   SQL Helpers   ////////////////////////////

    /**
     * Finds entries using an optional SQL statement
     * Unlike RedBean finder it doesn't use the bean ID as key (but keep the same order)
     * Great to extract data using array_map() for example
     *
     * @param string|null $sql SQL query to find the desired bean, starting right after WHERE clause
     * @param array $bindings array of values to be bound to parameters in query
     * @return static[]
     */
    public static function find(string $sql = null, array $bindings = []) {
        $result = [];
        if ($type = static::getType()) {
            foreach (Facade::find($type, $sql, $bindings) as $bean) {
                $result[] = $bean->box();
            }
        }
        return $result;
    }

    /**
     * Returns the first entry
     * @param string $sql SQL query to find the desired entry, starting right after WHERE clause
     * @param array $bindings array of values to be bound to parameters in query
     * @return static|null
     */
    public static function findOne(string $sql = "", array $bindings = []) {
        if ($type = static::getType()) {
            if ($result = Facade::findOne($type, $sql, $bindings)) return $result->box();
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
        if ($type = static::getType()) {
            return Facade::count($type, $sql, $bindings);
        }
        return 0;
    }

    ////////////////////////////   Basic Relations   ////////////////////////////

    /**
     * Defines Model Bean Type
     * @param Model|string $model
     * @return string|null
     */
    private function getModelType($model) {
        if ($model instanceof Model) {
            return $model->getMeta("type");
        } elseif (
                is_string($model) and class_exists($model)
                and in_array(self::class, class_parents($model))
        ) {
            return BeanHelper::$metadatas[$model]->type;
        }
        return null;
    }

    /**
     * Get many to many related Collection
     * @param Model|string $model Related Model
     * @return Collection|null
     */
    public function getSharedList($model) {
        $this->bean or static::create($this);
        if (
                ($type = $this->getModelType($model))
                and $type !== $this->getMeta($type)
        ) {
            $key = sprintf("shared%sList", ucfirst($type));
            $relatedClass = ($model instanceof Model) ? get_class($model) : $model;
            return Collection::create($this, $relatedClass, $key);
        }
        return null;
    }

    /**
     * Get one to many related Collection
     * @param Model|string $model Related Model
     * @return Collection|null
     */
    public function getOwnedList($model) {
        $this->bean or static::create($this);
        if (
                ($type = $this->getModelType($model))
                and $type !== $this->getMeta($type)
        ) {

            $key = sprintf("xown%sList", ucfirst($type));
            $relatedClass = ($model instanceof Model) ? get_class($model) : $model;
            return Collection::create($this, $relatedClass, $key);
        }
        return null;
    }

    /**
     * Get Many to one related Model
     * @param Model|string $model
     * @return Model|null
     */
    public function getListOwner($model) {
        $this->bean or static::create($this);
        if (
                ($type = $this->getModelType($model))
                and $type !== $this->getMeta($type)
        ) {

            if ($this->bean->exists($type)) return $this->bean->{$type}->box();
        }
        return null;
    }

    /**
     * Set Many to one related Model
     * @param Model$model
     * @return static
     */
    public function setListOwner(Model $model) {
        $this->bean or static::create($this);
        if (
                ($type = $this->getModelType($model))
                and $type !== $this->getMeta($type)
        ) {

            $this->bean->{$type} = $model->unbox();
        }
        return $this;
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
        $this->bean or static::create($this);
        $array = [];
        $props = array_merge(["id"], $this->getMeta("properties"));
        if ($this->getMeta("timestamps")) $props = array_merge($props, ["created_at", "updated_at"]);
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
        $i = static::load();
        foreach ($array as $k => $v) {
            $i->offsetSet($k, $v);
        }
        return $i;
    }

    /** {@inheritdoc} */
    public function offsetExists($offset) {
        $this->bean or static::create($this);
        $getter = $this->getGetterMethod($offset);
        return method_exists($this, $getter) && $this->{$getter}() !== null;
    }

    /** {@inheritdoc} */
    public function &offsetGet($offset) {
        $this->bean or static::create($this);
        $getter = $this->getGetterMethod($offset);
        if (method_exists($this, $getter)) {
            $value = $this->{$getter}();
            return $value;
        } else throw new InvalidProperty("Cannot access property " . get_class($this) . "::$" . $offset . ": No getter set.");
    }

    /** {@inheritdoc} */
    public function offsetSet($offset, $value) {
        $this->bean or static::create($this);
        $setter = $this->getSetterMethod($offset);
        if (method_exists($this, $setter)) {
            $this->{$setter}($value);
        } else throw new InvalidProperty("Cannot access property " . get_class($this) . "::$" . $offset . ": No setter set.");
    }

    /** {@inheritdoc} */
    public function offsetUnset($offset) {
        $this->bean or static::create($this);
        $setter = $this->getSetterMethod($offset);
        if (method_exists($this, $setter)) {
            $this->{$setter}(null);
        }
    }

    /** {@inheritdoc} */
    public function count() {
        $this->bean or static::create($this);
        return count($this->toArray());
    }

    /** {@inheritdoc} */
    public function getIterator() {
        $this->bean or static::create($this);
        return new ArrayIterator($this->toArray());
    }

    /** {@inheritdoc} */
    public function jsonSerialize() {
        return $this->toArray();
    }

    ////////////////////////////   Magics   ////////////////////////////

    /** {@inheritdoc} */
    public function &__get($prop) {
        $value = $this->offsetGet($prop);
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

    /**
     * @suppress PhanAccessReadOnlyMagicProperty
     * {@inheritdoc}
     */
    public function __clone() {
        if ($this->bean instanceof OODBBean) {
            $this->bean = clone $this->bean;
            $this->bean->setMeta("model", $this);
            $this->id = &$this->bean->id;
        }
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
     * Sync Model with Bean
     * @suppress PhanAccessReadOnlyMagicProperty
     * @internal
     */
    public function _load() {
        if ($meta = $this->getMeta()) {
            $b = $this->bean;
            $this->id = &$b->id;
            foreach ($meta->converters as $prop => $converter) {
                $value = $b->{$prop};
                if ($value !== null) $this->{$prop} = $converter::convertFromBean($value);
            }
            if ($meta->timestamps === true) {
                foreach (["created_at", "updated_at"] as $prop) {
                    $value = $b->{$prop};
                    if ($value !== null) $value = Date::convertFromBean($value);
                    $this->{$prop} = $value;
                }
            }
        }
    }

    /**
     * Validate Model Datas
     * @suppress PhanAccessReadOnlyMagicProperty
     * @internal
     * @suppress PhanUndeclaredMethod
     * @throws ValidationError Prevents Redbeans from Writing wrong datas
     */
    public function _validate() {

        $classname = get_class($this);

        if ($meta = $this->getMeta()) {
            foreach ($meta->required as $prop) {
                if ($this->{$prop} === null) throw new ValidationError($classname . '::$' . $prop . " Cannot be NULL");
            }

            foreach ($meta->converters as $prop => $converter) {
                if ($this->{$prop} === null) continue;
                if (!$converter::isValid($this->{$prop})) {
                    throw new ValidationError(
                            $classname . '::$' . $prop . " Invalid Type " .
                            $converter::getTypes()[0] . " requested but " .
                            gettype($this->{$prop}) . " given."
                    );
                }

                if (
                        method_exists($this, "validateModel")
                        and ( false === $this->validateModel($prop, $this->{$prop}))
                ) {
                    throw new ValidationError($classname . "::validateModel($prop, ...) failed the validation test.");
                }
            }
        }
    }

    /**
     * Write datas to bean
     * @suppress PhanAccessReadOnlyMagicProperty
     * @internal
     * @throws ValidationError Prevents Redbeans from Writing wrong datas
     */
    public function _update() {
        $classname = get_class($this);
        $unique = $this->getMeta('unique');

        if ($meta = $this->getMeta()) {
            $b = $this->bean;
            if ($meta->timestamps === true) {
                $now = new DateTime();
                if ($this->created_at === null) $this->created_at = $b->created_at = $now;
                $this->updated_at = $b->updated_at = $now;
            }
            foreach ($meta->converters as $prop => $converter) {
                if ($prop === "id") continue;
                $value = $converter::convertToBean($this->{$prop});
                $this->bean->{$prop} = $value;
                //checks unique value
                if (in_array($prop, $unique)) {
                    if ($model = $classname::findOne('? = ?', [$prop, $value])) {
                        if ($this->id != $model->id) {
                            throw new ValidationError($classname . '::$' . $prop . " Unique Value $current already exists.");
                        }
                    }
                }
            }
        }
    }

}
