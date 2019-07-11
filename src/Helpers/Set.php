<?php

declare(strict_types=1);

namespace Manju\Helpers;

use InvalidArgumentException;
use Manju\{
    Exceptions\InvalidProperty, ORM\Model
};
use RedBeanPHP\OODBBean;
use function NGSOFT\Tools\{
    noop, toCamelCase
};

/**
 * Collection Object for related beans based on doctrine/collection
 * @property-read int $length
 */
class Set {

    /** @var array<Model> */
    private $list = [];

    /** @var OODBBean */
    private $bean;

    /** @var Model */
    private $model;

    /** @var string */
    private $key;

    /** @var string */
    private $relatedClass;

    public function __construct(Model $model, string $relatedClass, string $key) {
        $this->key = $key;
        $this->relatedClass = $relatedClass;
        $this->model = $model;
        $b = $this->bean = $model->unbox();
        $list = $b->{$key};

        if (is_array($list)) {
            foreach ($list as $bean) {
                $this->list[] = $bean->box();
            }
        }
    }

    ////////////////////////////   Operations   ////////////////////////////

    /**
     * Save the Base Model + the associations
     * @return static
     */
    public function save() {
        $this->model->save();
        return $this;
    }

    ////////////////////////////   Utils   ////////////////////////////

    /**
     * @param Model $model
     * @throws InvalidArgumentException
     */
    private function assertModel(Model $model) {
        if (
                (get_class($model) !== $this->relatedClass)
                or $model->id === 0
        ) {
            throw new InvalidArgumentException(
                    "Invalid Model : " . $this->relatedClass
                    . " requested but " . get_class($model) . " given"
            );
        }
    }

    /**
     * Get the current Collection length
     * @return int
     */
    public function getLength(): int {
        return count($this->list);
    }

    /**
     * Filters elements in the set in place using a callback function
     * @param callable $condition
     * @return static
     */
    public function filter(callable $condition) {
        $list = [];
        foreach ($this->list as $model) {
            assert(is_bool(($retval = $condition($model))));
            if (false === $condition) unset($this->bean->{$this->key}[$model->id]);
            else $list[] = $model;
        }
        if (count($list) !== $this->getLength()) $this->list = $list;
        return $this;
    }

    /**
     * Applies the callback to the elements of the set
     * @param callable $callback
     * @return array
     */
    public function map(callable $callback): array {
        return array_map($callback, $this->list);
    }

    /**
     * Tests whether all elements in the set pass the test implemented by the provided function
     * @param callable $callback
     * @return bool
     */
    public function every(callable $callback): bool {
        foreach ($this->list as $model) {
            assert(is_bool(($retval = $callback($model))));
            if (true !== $retval) return false;
        }
        return true;
    }

    /**
     * Tests whether at leas one element in the set pass the test implemented by the provided function
     * @param callable $callback
     * @return bool
     */
    public function some(callable $callback) {
        foreach ($this->list as $model) {
            assert(is_bool(($retval = $callback($model))));
            if (true === $retval) return true;
        }
        return false;
    }

    /**
     * Use the user provided function to sort the set
     * @param callable $callback
     * @return $this
     */
    public function sort(callable $callback) {
        uasort($this->list, $callback);
        return $this;
    }

    /**
     * Get them provided model index
     * @param Model $model
     * @return int -1 if provided model not found
     */
    public function indexOf(Model $model): int {
        $this->assertModel($model);
        $result = array_search($model, $this->list, true);
        if (false === $result) return -1;
        return $result;
    }

    ////////////////////////////   Methods   ////////////////////////////

    /**
     * Add a related Model to the List/Bean
     * @param Model $model
     * @return static
     */
    public function add(Model $model) {
        $this->assertModel($model);
        if ($this->indexOf($model) === -1) {
            $this->list[] = $model;
            $this->bean->{$this->key}[] = $model->unbox();
        }
        return $this;
    }

    /**
     * Add Multiple related Models
     * @param array<Model> $models
     * @return static
     */
    public function addMultiple(array $models) {
        foreach ($models as $model) {
            $this->add($model);
        }
        return $this;
    }

    /**
     * Removes a model from the set
     * @param Model $model
     * @return static
     */
    public function remove(Model $model) {
        $this->assertModel($model);
        if (($i = $this->indexOf($model)) !== -1) unset($this->list[$i], $this->bean->{$this->key}[$model->id]);
        return $this;
    }

    /**
     * Removes multiple models from the set
     * @param array<Model> $models
     * @return static
     */
    public function removeMultiple(array $models) {
        foreach ($models as $model) {
            $this->remove($model);
        }
        return $this;
    }

    /**
     * Check if set has given model
     * @param Model $model
     */
    public function has(Model $model) {
        return $this->indexOf($model) !== -1;
    }

    /**
     * Clears the set, also remove entries from the related bean
     * @return static
     */
    public function clear() {
        $this->list = [];
        $this->bean->{$this->key} = [];
        return $this;
    }

    /**
     * Get First Model
     * @return Model|null
     */
    public function first() {
        foreach ($this->list as $model) {
            return $model;
        }
        return null;
    }

    /**
     * Get last Model
     * @return Model|null
     */
    public function last() {
        foreach (array_reverse($this->list) as $model) {
            return $model;
        }
        return null;
    }

    ////////////////////////////   _SEPARATOR__magics   ////////////////////////////

    /** {@inheritdoc} */
    public function __get($prop) {
        $method = sprintf("get%s", toCamelCase($prop));
        if (!method_exists($this, $method)) throw new InvalidProperty(get_class($this) . "::$" . $prop . " Does not exists.");
        return $this->{$method}();
    }

    /** {@inheritdoc} */
    public function __set($p, $v) {
        noop($p, $v);
    }

    /** {@inheritdoc} */
    public function __unset($p) {
        noop($p);
    }

}
