<?php

declare(strict_types=1);

namespace Manju\ORM;

use Manju\ORM\Model,
    RedBeanPHP\OODBBean;

class Bean extends OODBBean {

    /**
     * Get Related Model
     * @return Model|null
     * @internal
     */
    private function getModel(): ?Model {
        if (
                ($model = $this->getMeta("model"))
                and $model instanceof Model
        ) return $model;
        return null;
    }

    /**
     * Event triggered when a bean is loaded from database
     * ORM::load()
     * @suppress PhanUndeclaredMethod
     */
    public function open() {
        if ($model = $this->getModel()) {
            $model->_load();
            if (method_exists($model, "open")) $model->open();
        }
    }

    /**
     * Event triggered just after a bean is saved into the database
     * ORM::save()
     * @suppress PhanUndeclaredMethod
     */
    public function after_update() {
        if ($model = $this->getModel()) {
            $model->_load(); //sync the id
            if (method_exists($model, "after_update")) $model->after_update();
        }
    }

    /**
     * Event triggered just before a bean is saved into the database
     * ORM::save()
     * @suppress PhanUndeclaredMethod
     */
    public function update() {
        if ($model = $this->getModel()) {
            if (method_exists($model, "update")) $model->update();
            $model->_validate();  //use validators
            $model->_update(); //inject model data into bean
        }
    }

}
