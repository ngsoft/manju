<?php

declare(strict_types=1);

namespace NGSOFT\Manju;

/**
 * Replaces the SimpleModel
 */
class Entity extends \RedBeanPHP\SimpleModel {

    public function __get($prop) {
        return parent::__get($prop);
    }

    public function __isset($key) {
        return parent::__isset($key);
    }

    public function __set($prop, $value) {
        parent::__set($prop, $value);
    }

    public function box() {
        return parent::box();
    }

    public function loadBean(\RedBeanPHP\OODBBean $bean) {
        parent::loadBean($bean);
    }

    public function unbox() {
        return parent::unbox();
    }

}
