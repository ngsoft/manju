<?php

namespace Manju\Helpers;

use RedBeanPHP\BeanHelper\SimpleFacadeBeanHelper;

class BeanHelper extends SimpleFacadeBeanHelper {

    public function getModelForBean(\RedBeanPHP\OODBBean $bean) {
        return parent::getModelForBean($bean);
    }

}
