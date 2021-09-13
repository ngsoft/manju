<?php

declare(strict_types=1);

namespace NGSOFT\Manju;

use RedBeanPHP\OODBBean;

class Bean extends OODBBean {

    public function __call($method, $args) {

        if (in_array($method, array('update', 'open', 'delete', 'after_delete', 'after_update', 'dispense'), TRUE)) {






            return NULL;
        }



        parent::__call($method, $args);
    }

}
