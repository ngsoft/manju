<?php

namespace Manju;
use RedBeanPHP\SimpleModel;
use RedBeanPHP\OODBBean;
use RedBeanPHP\Facade as R;
if (version_compare(PHP_VERSION, '7.0', '<')) {
    throw new Exception('This program needs php version > 7.0 to run correctly.');
}

abstract class Bun extends SimpleModel{
    
    const MANJU_VERSION = '1.0.alpha1';
    
    /**
     * Type of the bean
     * @param string $bean
     */
    public static $beantype;
    
    
    /**
     * @var OODBBean
     */
    protected $bean;





    public function __construct($bean = null) {
        $bean = is_null($bean)?false:$bean;
    }
    
    
    private function initialize($bean = null){
        if(is_bool($bean)) return;
        
    }
    
    
    


























    public function __get($prop) {
        return $this->bean->$prop;
    }

    public function __isset($key) {
        return isset( $this->bean->$key );
    }

    public function __set($prop, $value): void {
        $this->bean->$prop = $value;
    }

    public function box() {
        return $this;
    }

    public function loadBean(\RedBeanPHP\OODBBean $bean){
        $this-
    }

    public function unbox() {
        return $this->bean;
    }

    
    
    
}
