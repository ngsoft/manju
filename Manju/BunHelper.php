<?php

namespace Manju;

use RedBeanPHP\BeanHelper\SimpleFacadeBeanHelper;
use RedBeanPHP\OODBBean;
use RedBeanPHP\Facade as R;


class BunHelper extends SimpleFacadeBeanHelper{
    
    private static $caster;
    
    
    public static function register(){
        if(R::getRedBean()->getBeanHelper() instanceof self) return;
        R::getRedBean()->setBeanHelper(new self);
    }
    
    public static function unregister(){
        R::getRedBean()->setBeanHelper(new SimpleFacadeBeanHelper);
        
    }

    
    
    public static function dispense(Bun &$model){
        self::setCaster($model);
        return R::dispense($model->beantype());
    }
    
    
    public static function load(Bun $model, int $id){
        self::setCaster($model);
        $beantype = $model->beantype();
        return R::load($beantype, $id);
    }
    
    private static function setCaster(Bun $model){
        self::$caster = $model;
    }
    
    private static function unsetCaster(){
        self::$caster = null;
    }
    
    
    /**
     * Overrides RedBeanPHP default behavior
     * @param OODBBean $bean
     * @return Manju\Bun | RedBeanPHP\SimpleModel | null
     */
    public function getModelForBean(OODBBean $bean) {
        $model = $bean->getMeta( 'type' );
        if(self::$caster){
            if($type == self::$caster->beantype()){
                $obj = self::$caster;
                $obj->loadBean($bean);
            }
            self::unsetCaster();
        }
        elseif(isset(Bun::$beanlist[$model])){
            $obj = self::factory( Bun::$beanlist[$model] );
            $obj->loadBean( $bean );
            return $obj;
        }
        if(isset($obj)) return $obj;
        return parent::getModelForBean($bean);
    }
    
    
    
    
}
