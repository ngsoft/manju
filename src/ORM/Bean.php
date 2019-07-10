<?php

namespace Manju\ORM;

use Closure,
    Manju\ORM\Model,
    RedBeanPHP\OODBBean;

class Bean extends OODBBean {

    /**
     * Get Related Model
     * @return Model|null
     * @internal
     */
    private function getModel(): ?Model {
        if ($model = $this->getMeta("model") and $model instanceof Model) return $model;
        return null;
    }

    /**
     * Binds a callback to execute private methods
     * @param Model $model
     * @param string $method
     * @param mixed ...$args
     */
    private function executePrivateMethodModel(Model $model, string $method, ...$args) {
        $c = function(string $method, ...$args) {
            if (method_exists($this, $method)) return $this->{$method}(...$args);
        };
        $c = Closure::bind($c, $model);
        return $c($method, ...$args);
    }

    public function dispense() {
        ($model = $this->getModel()) and $this->executePrivateMethodModel($model, '_reload');
    }

    public function open() {
        ($model = $this->getModel()) and $this->executePrivateMethodModel($model, '_reload');
    }

    public function after_update() {
        ($model = $this->getModel()) and $this->executePrivateMethodModel($model, '_reload');
    }

    public function after_delete() {
        ($model = $this->getModel()) and $this->executePrivateMethodModel($model, '_clear');
    }

    public function update() {
        if ($model = $this->getModel()) {
            $this->executePrivateMethodModel($model, '_validate'); //use validators
            $this->executePrivateMethodModel($model, '_update'); //inject model data into bean
        }
    }

    public function delete() {

    }

}
