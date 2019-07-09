<?php

namespace Manju\Helpers;

use Manju\ORM\Model,
    RedBeanPHP\OODBBean;

class Bean extends OODBBean {

    /**
     * Get Related Model
     * @return Model|null
     * @internal
     */
    private function getModel(): ?Model {
        if ($model = $this->getMeta("model") and $model instanceof Model) return $model;
    }

    public function dispense() {
        ($model = $this->getModel())and $model->_reload();
    }

    public function open() {
        ($model = $this->getModel()) and $model->_reload();
    }

    public function after_update() {
        ($model = $this->getModel()) and $model->_reload();
    }

    public function after_delete() {
        if ($model = $this->getModel()) $model->_clear();
    }

    public function update() {
        if ($model = $this->getModel()) {
            //$model->__validate(); //use validators
            //$model->__update(); //inject model data into bean
        }
    }

    public function delete() {

    }

}
