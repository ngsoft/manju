<?php

namespace Manju\Helpers;

use ArrayAccess,
    ArrayIterator,
    Countable,
    IteratorAggregate;
use Manju\{
    Exceptions\ManjuException, ORM\Model
};
use OutOfBoundsException,
    OutOfRangeException,
    RedBeanPHP\OODBBean;

class Collection implements ArrayAccess, IteratorAggregate, Countable {

    /** @var OODBBean[] */
    private $list = [];

    /** @var string */
    private $related;

    /** @var OODBBean */
    private $bean;

    /** @var string */
    private $key;

    ////////////////////////////   Constructor   ////////////////////////////

    public static function create(Model $model, string $related, string $key) {
        if (!class_exists($related)) throw new ManjuException("Related class $related does not exists.");
        if (!in_array(Model::class, class_parents($related))) {
            throw new ManjuException("Related class $related does nor extends " . Model::class);
        }

        if (!preg_match(Model::TO_MANY_LIST, $key)) throw new ManjuException("$key is invalid.");

        $instance = new static();
        $instance->related = $related;
        $instance->key = $key;
        $instance->bean = $model->unbox();
        $instance->list = &$instance->bean->{$key};
        return $instance;
    }

    ////////////////////////////   Utils   ////////////////////////////


    private function assertModel(Model $model) {
        if (get_class($model) !== $this->related) {
            throw new ManjuException(
                    "Invalid Model " . get_class($model)
                    . " Not " . $this->related
            );
        }
    }

    /**
     * Get the Model from the bean
     * @return Model
     */
    private function getModel(): Model {
        return $this->bean->getMeta("model");
    }

    /**
     * Export data
     * @return Model[]
     */
    public function toArray(): array {
        $result = [];
        foreach ($this->list as $bean) {
            $result[] = $bean->getMeta("model");
        }
        return $result;
    }

    ////////////////////////////   Methods   ////////////////////////////

    /**
     * Check if model already set
     * @param Model $model
     * @return bool
     */
    public function hasItem(Model $model): bool {
        $this->assertModel($model);
        $ids = [];
        foreach ($this->list as $bean) {
            $ids[] = (int) $bean->id;
        }
        return in_array($model->id, $ids);
    }

    /**
     * Add a Related Model
     * @param Model $model
     */
    public function addItem(Model $model) {
        $this->assertModel($model);
        if (!$this->hasItem($model)) {
            $this->list[] = $model->unbox();
        }
    }

    /**
     * Removes a Related Model
     * @param Model $model
     */
    public function removeItem(Model $model) {
        $this->assertModel($model);
        if ($this->hasItem($model)) {
            $id = $model->id;
            $this->list = array_filter($this->list, function (OODBBean $bean) use ($id) {
                return $bean->id !== $id;
            });
        }
    }

    /**
     * Overwrite the current list
     * @param iterable<Model> $items
     */
    public function setItems(iterable $items) {
        $list = [];
        foreach ($items as $model) {
            if ($model instanceof Model) {
                $this->assertModel($model);
                $list[] = $model->unbox();
            } else throw new ManjuException("Invalid item supplied.");
        }
        $this->list = $list;
    }

    /**
     * Clears the list
     */
    public function clear() {
        $this->list = [];
    }

    /**
     * Save the changes
     * @param bool $throw Throws ValidationError on error
     * @return int|null
     */
    public function save(bool $throw = false) {
        return $this->getModel()->save($throw);
    }

    ////////////////////////////   ArrayAccess   ////////////////////////////

    /** {@inheritdoc} */
    public function getIterator() {
        $array = $this->toArray();
        return new ArrayIterator($array);
    }

    /** {@inheritdoc} */
    public function offsetExists($offset) {
        return isset($this->list[$offset]);
    }

    /** {@inheritdoc} */
    public function offsetGet($offset) {

        if (!is_int($offset)) {
            throw new OutOfBoundsException("Invalid Offset $offset");
        }

        if (!$this->offsetExists($offset)) {
            throw new OutOfRangeException("Invalid Index $offset");
        }
        return $this->list[$offset]->getMeta("model");
    }

    /** {@inheritdoc} */
    public function offsetSet($offset, $value): void {
        $this->assertModel($value);
        if (is_string($offset)) {
            throw new OutOfBoundsException("Invalid Offset $offset");
        }
        if (is_int($offset)) {
            throw new OutOfRangeException("Cannot overwrite Index $offset");
        }
        if (is_null($offset)) $this->addItem($value);
    }

    /** {@inheritdoc} */
    public function offsetUnset($offset) {
        if ($this->offsetExists($offset)) unset($this->list[$offset]);
    }

    public function count() {
        return count($this->list);
    }

}
