<?php

namespace Manju;

use RedBeanPHP\BeanHelper\SimpleFacadeBeanHelper;
use RedBeanPHP\OODBBean;
use RedBeanPHP\Facade as R;

class BeanHelper extends SimpleFacadeBeanHelper{
    
    public static $registered = false;
    public static $enabled = true;

    public function __construct() {
        if(!self::$registered){
            R::getRedBean()->setBeanHelper($this);
            self::$registered = true;
        }
    }
    
    /**
     * Overrides RedBeanPHP default behavior
     * @param OODBBean $bean
     * @return Manju\Bun | RedBeanPHP\SimpleModel | null
     */
    public function getModelForBean(OODBBean $bean) {
        if(!self::$enabled){
            $obj = null;
            return $obj;
        }
        $model = $bean->getMeta( 'type' );
        if(isset(Bun::$beanlist[$model])){
            $obj = self::factory( Bun::$beanlist[$model] );
            $obj->loadBean( $bean );
            return $obj;
        }
        return parent::getModelForBean($bean);
    }
    
    
    
    
    
    
}
