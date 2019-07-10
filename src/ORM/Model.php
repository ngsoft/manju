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
    protected $id;

    /**
     * @var DateTime|null
     */
    protected $created_at = null;

    /**
     * @var DateTime|null
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

////////////////////////////   SQL Helpers   ////////////////////////////

    /**
     * Finds entries using an optional SQL statement
     * @param string|null $sql SQL query to find the desired bean, starting right after WHERE clause
     * @param array $bindings array of values to be bound to parameters in query
     * @return array<static>
     */
    public static function find(string $sql = null, array $bindings = []): array {
        if (($type = BeanHelper::$metadatas[static::class]["type"] ?? null)) {
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
        if (($type = BeanHelper::$metadatas[static::class]["type"] ?? null)) {
            if ($result = ORM::findOne($type, $sql, $bindings)) return $result->box();
        }
        return null;
    }

    /**
     * Count the number of entries of current Model
     * @param string $sql additional SQL snippe
     * @param array $bindings parameters to bind to SQL
     * @return int
     */
    public static function countEntries(string $sql = "", array $bindings = []): int {
        if (($type = BeanHelper::$metadatas[static::class]["type"] ?? null)) {
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
    public function store(): ?int {

        if ($this->bean instanceof OODBBean) {
            $this->bean->setMeta("tainted", true);
            return (int) ORM::store($this->bean);
        }
        return null;
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
        return $meta[$key] ?? null;
    }

////////////////////////////   Relations   ////////////////////////////

    /**
     * Loads Relation Mapper on demand
     * you need to run that before accessing a relation key
     * @return $this
     */
    public function loadRelations() {

        if (
                ($meta = $this->getMeta())
                and $this->bean instanceof OODBBean
        ) {
            foreach ($meta["relations"] as $key => $relation) {
                $b = $this->bean; $value = null;
                $type = strtolower($relation["type"]);
                switch ($type) {
                    case "onetomany":
                        $skey = sprintf('xown%sList', ucfirst(BeanHelper::$metadatas[$relation["target"]]["type"]));
                    case "manytomany":
                        $skey = $skey ?? sprintf('shared%sList', ucfirst(BeanHelper::$metadatas[$relation["target"]]["type"]));
                        if ($via = $relation["via"] ?? null) $b = $b->via($via);
                        $value = array_merge([], $b->{$skey});
                        $value = array_map(function ($bean) { return $bean->box(); }, $value);
                        break;
                    case "manytoone":
                        $skey = BeanHelper::$metadatas[$relation["target"]]["type"];
                        $value = $b->{$skey};
                        break;
                }
                $this->{$key} = $value;
            }
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
        throw new InvalidProperty("Invalid Property $offset");
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
            foreach (array_keys($meta["relations"]) as $key) {
                $this->{$key} = null;
            }
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
            foreach ($meta["converters"] as $converter => $prop) {
                $value = $b->{$prop};
                if ($value !== null) $this->{$prop} = $converter->convertFromBean($value);
            }
            if (count($meta["unique"])) $b->setMeta("sys.uniques", $meta["unique"]);
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
            foreach ($meta["required"] as $prop) {
                if ($this->{$prop} === null) throw new ValidationError(get_class($this) . '::$' . $prop . " Cannot be NULL");
            }
            foreach ($meta["converter"] as $prop => $converter) {
                if (!$converter->isValid($this->{$prop})) {
                    throw new ValidationError(
                            get_class($this) . '::$' . $prop . " Invalid Type " .
                            $converter->getTypes()[0] . " requested but " .
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
            if ($meta["timestamps"] === true) {
                $now = time();
                if ($this->created_at === null) {
                    $this->created_at = $meta["converters"]["created_at"]->convertFromBean($now);
                }
                $this->updated_at = $meta["converters"]["updated_at"]->convertFromBean($now);
            }
            foreach ($meta["converters"] as $prop => $converter) {
                if ($prop === "id") continue;
                $value = $converter->convertToBean($this->{$prop});
                $this->bean->{$prop} = $value;
            }
            //relations
            $b = $this->bean;
            foreach ($meta["relations"] as $key => $relation) {
                if (!isset($this->{$key})) continue;
                $type = strtolower($relation["type"]);
                switch ($type) {
                    case "onetomany":
                        $skey = sprintf('xown%sList', ucfirst(BeanHelper::$metadatas[$relation["target"]]["type"]));
                    case "manytomany":
                        $skey = $skey ?? sprintf('shared%sList', ucfirst(BeanHelper::$metadatas[$relation["target"]]["type"]));
                        if ($via = $relation["via"] ?? null) $b = $b->via($via);
                        $b->{$skey} = array_map(function ($model) { return $model->unbox(); }, $this->{$key});
                        break;
                    case "manytoone":
                        $skey = BeanHelper::$metadatas[$relation["target"]]["type"];
                        $b->{$skey} = $this->{$key};
                        break;
                }
            }
        }
    }

}
