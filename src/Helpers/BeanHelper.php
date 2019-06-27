<?php

namespace Manju\Helpers;

use Manju\ORM\Model;
use RedBeanPHP\BeanHelper\SimpleFacadeBeanHelper;
use RedBeanPHP\OODBBean;

class BeanHelper extends SimpleFacadeBeanHelper {

    /** @var array<string,string> */
    protected static $models = [];

    public static function addModel(Model $model) {
        $models[$model::beanType()] = get_class($model);
    }

    public function getModelForBean(OODBBean $bean) {





        return parent::getModelForBean($bean);
    }

}
