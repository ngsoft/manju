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

    public function dispense() {
        ($model = $this->getModel()) and $model->_reload();
    }

    public function open() {
        ($model = $this->getModel()) and $model->_reload();
    }

    public function after_update() {
        ($model = $this->getModel()) and $model->_reload();
    }

    public function after_delete() {
        ($model = $this->getModel()) and $model->_clear();
    }

    public function update() {
        if ($model = $this->getModel()) {
            $model->_validate(); ; //use validators
            $model->_update($model, '_update'); //inject model data into bean
        }
    }

    public function delete() {

    }

}
